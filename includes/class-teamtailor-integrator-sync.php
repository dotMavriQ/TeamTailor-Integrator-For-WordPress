<?php
/**
 * TeamTailor synchronization functionality
 *
 * @since      1.0.0
 *
 * @package    TeamTailor_Integrator
 * @subpackage TeamTailor_Integrator/includes
 */

/**
 * TeamTailor synchronization functionality.
 *
 * Syncs jobs from TeamTailor to WordPress.
 *
 * @package    TeamTailor_Integrator
 * @subpackage TeamTailor_Integrator/includes
 * @author     Jonatan Jansson
 */
class TeamTailor_Integrator_Sync {

    /**
     * The API integration.
     *
     * @since    1.0.0
     * @access   private
     * @var      TeamTailor_Integrator_API    $api    The API integration.
     */
    private $api;

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // We'll initialize the API in the sync_teamtailor method to ensure
        // we always use the latest API key from the settings
    }

    /**
     * Get post ID by job ID.
     *
     * @since    1.0.0
     * @param    string    $job_id    The job ID.
     * @return   int|null            The post ID or null if not found.
     */
    public function get_post_id_by_job_id($job_id) {
        $query = new WP_Query([
            'post_type' => 'teamtailor_jobs',
            'meta_query' => [
                [
                    'key' => '_teamtailor_job_id',
                    'value' => $job_id,
                    'compare' => '=',
                ],
            ],
            'posts_per_page' => 1,
        ]);
        
        if ($query->have_posts()) {
            return $query->posts[0]->ID;
        }
        
        return null;
    }

    /**
     * Get existing job IDs.
     *
     * @since    1.0.0
     * @return   array    The existing job IDs.
     */
    public function get_existing_job_ids() {
        $query = new WP_Query([
            'post_type' => 'teamtailor_jobs',
            'posts_per_page' => -1,
        ]);
        
        $ids = [];
        foreach ($query->posts as $post) {
            $ids[] = get_post_meta($post->ID, '_teamtailor_job_id', true);
        }
        
        return $ids;
    }

    /**
     * Sync jobs from TeamTailor.
     *
     * @since    1.0.0
     */
    public function sync_teamtailor() {
        $api_key = get_option('teamtailor_integrator_api_token');
        $debug_mode = get_option('teamtailor_integrator_debug_mode');
        
        // Output debug info only when debug mode is on
        if ($debug_mode) {
            echo '<div class="teamtailor-status-box">';
            echo '<p><strong>▶</strong> Starting sync</p>';
            echo '</div>';
        }
        
        if (!$api_key) {
            echo '<div class="teamtailor-notice teamtailor-notice-error">';
            echo '<p>API Key is not set. Please configure your API token in the Settings tab before syncing.</p>';
            echo '</div>';
            return;
        }

        if ($debug_mode) {
            echo '<div class="teamtailor-status-box">';
            echo '<p><strong>▶</strong> Connecting w/ token: ' . substr($api_key, 0, 5) . '...</p>';
            echo '</div>';
        }

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-teamtailor-integrator-api.php';
        $this->api = new TeamTailor_Integrator_API($api_key, $debug_mode);
        
        // Show debug message if debug mode is on
        if ($debug_mode) {
            echo '<div class="teamtailor-status-box">';
            echo '<p><strong>▶</strong> Fetching job listings</p>';
            echo '</div>';
        }
        
        $jobs = $this->api->get_jobs();
        
        if (!is_array($jobs)) {
            echo '<div class="teamtailor-notice teamtailor-notice-error">';
            echo '<p>Error fetching data from TeamTailor. Please check your API token and try again.</p>';
            if ($debug_mode) {
                echo '<p>Debug info: ' . print_r($jobs, true) . '</p>';
            }
            echo '</div>';
            return;
        }
        
        if (empty($jobs['data'])) {
            echo '<div class="teamtailor-notice teamtailor-notice-warning">';
            echo '<p>No jobs found in TeamTailor API response. The API connected successfully but no job listings were returned.</p>';
            echo '</div>';
            return;
        }

        if ($debug_mode) {
            echo '<div class="teamtailor-status-box">';
            echo '<p><strong>▶</strong> Processing job data</p>';
            echo '</div>';
        }

        $existing_ids = $this->get_existing_job_ids();
        $company_name = $this->api->get_company_name();
        $jobs_synced = 0;
        $jobs_updated = 0;
        $job_count = count($jobs['data'] ?? []);
        
        foreach ($jobs['data'] as $job) {
            $job_id = $job['id'];
            $post_id = $this->get_post_id_by_job_id($job_id);
            $job_title = $job['attributes']['title'];
            $job_body = $job['attributes']['body'];

            // Fetch additional data
            $departments_data = $this->api->fetch_data("jobs/$job_id/department");
            $locations_data = $this->api->fetch_data("jobs/$job_id/locations");
            $extracted_locations = $this->api->extract_locations($locations_data);
            $role_name = $this->api->get_role_name($job_id);

            $post_content = $job_body;
            $job_apply_iframe_url = $job['links']['careersite-job-apply-iframe-url'] ?? '';
            
            if ($job_apply_iframe_url) {
                $post_content .= "\n\n<iframe src='" . esc_url($job_apply_iframe_url) . "' style='width: 100%; height: 800px' frameborder='0'></iframe>";
            }

            $post_data = [
                'post_type' => 'teamtailor_jobs',
                'post_title' => $job_title,
                'post_content' => $post_content,
                'post_status' => 'publish',
                'meta_input' => [
                    '_teamtailor_job_id' => $job_id,
                    'departments' => $this->api->extract_department_name($departments_data),
                    'locations' => $extracted_locations['locations'],
                    'countries' => $extracted_locations['countries'],
                    'roles' => $role_name,
                    'company' => $company_name,
                ],
            ];

            if ($post_id) {
                $post_data['ID'] = $post_id;
                wp_update_post($post_data);
                $jobs_updated++;
            } else {
                wp_insert_post($post_data);
                $jobs_synced++;
            }

            if (($key = array_search($job_id, $existing_ids)) !== false) {
                unset($existing_ids[$key]);
            }
        }

        // Remove posts that no longer exist in the TeamTailor data
        $jobs_removed = 0;
        foreach ($existing_ids as $id) {
            $post_id = $this->get_post_id_by_job_id($id);
            if ($post_id) {
                wp_delete_post($post_id, true);
                $jobs_removed++;
            }
        }

        // Format summary message
        $summary = array();
        
        if ($jobs_synced > 0) {
            $summary[] = sprintf('<strong>%d</strong> new %s imported', 
                $jobs_synced, 
                $jobs_synced === 1 ? 'job' : 'jobs'
            );
        }
        
        if ($jobs_updated > 0) {
            $summary[] = sprintf('<strong>%d</strong> existing %s updated', 
                $jobs_updated, 
                $jobs_updated === 1 ? 'job' : 'jobs'
            );
        }
        
        if ($jobs_removed > 0) {
            $summary[] = sprintf('<strong>%d</strong> obsolete %s removed', 
                $jobs_removed, 
                $jobs_removed === 1 ? 'job' : 'jobs'
            );
        }
        
        if (empty($summary)) {
            $summary[] = 'No changes made';
        }

        // Always show the sync completion message regardless of debug mode
        echo '<div class="teamtailor-notice teamtailor-notice-success">';
        echo '<p><strong>Sync completed successfully!</strong></p>';
        echo '<p>' . implode(', ', $summary) . '.</p>';
        echo '</div>';
        
        // Add view all jobs link
        echo '<p><a href="' . esc_url(admin_url('edit.php?post_type=teamtailor_jobs')) . '" class="button button-secondary">View All Jobs</a></p>';
    }
}
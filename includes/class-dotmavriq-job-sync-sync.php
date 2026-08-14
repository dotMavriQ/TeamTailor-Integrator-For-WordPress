<?php
/**
 * TeamTailor synchronization functionality
 *
 * @since      1.0.0
 *
 * @package    DotMavriQ_Job_Sync
 * @subpackage DotMavriQ_Job_Sync/includes
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * TeamTailor synchronization functionality.
 *
 * Syncs jobs from TeamTailor to WordPress.
 *
 * @package    DotMavriQ_Job_Sync
 * @subpackage DotMavriQ_Job_Sync/includes
 * @author     Jonatan Jansson
 */
class DotMavriQ_Job_Sync_Sync {

    /**
     * The API integration instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      DotMavriQ_Job_Sync_API    $api    The API integration.
     */
    private $api;

    /**
     * Initialize the class.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // API is initialized in sync_teamtailor() to always use the latest settings.
    }

    /**
     * Get post ID by TeamTailor job ID.
     *
     * @since    1.0.0
     * @param    string    $job_id    The TeamTailor job ID.
     * @return   int|null             The post ID or null if not found.
     */
    public function get_post_id_by_job_id( $job_id ) {
        $query = new WP_Query(
            array(
                'post_type'      => 'teamtailor_jobs',
                'posts_per_page' => 1,
                'meta_query'     => array(
                    array(
                        'key'     => '_teamtailor_job_id',
                        'value'   => $job_id,
                        'compare' => '=',
                    ),
                ),
            )
        );

        if ( $query->have_posts() ) {
            return $query->posts[0]->ID;
        }

        return null;
    }

    /**
     * Get existing TeamTailor job IDs from WordPress.
     *
     * @since    1.0.0
     * @return    array    The existing job IDs.
     */
    public function get_existing_job_ids() {
        $query = new WP_Query(
            array(
                'post_type'      => 'teamtailor_jobs',
                'posts_per_page' => -1,
            )
        );

        $ids = array();
        foreach ( $query->posts as $post ) {
            $ids[] = get_post_meta( $post->ID, '_teamtailor_job_id', true );
        }

        return $ids;
    }

    /**
     * Sync jobs from TeamTailor to WordPress.
     *
     * @since    1.0.0
     */
    public function sync_teamtailor() {
        $api_key    = get_option( 'teamtailor_integrator_api_token' );
        $debug_mode = get_option( 'teamtailor_integrator_debug_mode' );
        $use_mock   = (bool) get_option( 'teamtailor_integrator_use_mock_data', false );

        if ( $debug_mode ) {
            echo '<div class="teamtailor-status-box">';
            echo '<p><strong>▶</strong> ' . esc_html__( 'Starting sync', 'dotmavriq-job-sync') . '</p>';
            echo '</div>';
        }

        if ( ! $api_key && ! $use_mock ) {
            echo '<div class="teamtailor-notice teamtailor-notice-error">';
            echo '<p>' . esc_html__( 'API Key is not set. Please configure your API token in the Settings tab before syncing, or enable "Use Mock Data" in the Advanced tab for testing without a live API key.', 'dotmavriq-job-sync') . '</p>';
            echo '</div>';
            return;
        }

        if ( $debug_mode ) {
            echo '<div class="teamtailor-status-box">';
            echo '<p><strong>▶</strong> ' . esc_html__( 'Connecting with token:', 'dotmavriq-job-sync') . ' ' . esc_html( substr( $api_key, 0, 5 ) ) . '...</p>';
            echo '</div>';
        }

        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-dotmavriq-job-sync-api.php';
        $this->api = new DotMavriQ_Job_Sync_API( $api_key, $debug_mode );

        if ( $debug_mode ) {
            echo '<div class="teamtailor-status-box">';
            echo '<p><strong>▶</strong> ' . esc_html__( 'Fetching job listings', 'dotmavriq-job-sync') . '</p>';
            echo '</div>';
        }

        $jobs = $this->api->get_jobs();

        if ( ! is_array( $jobs ) ) {
            echo '<div class="teamtailor-notice teamtailor-notice-error">';
            echo '<p>' . esc_html__( 'Error fetching data from TeamTailor. Please check your API token and try again.', 'dotmavriq-job-sync') . '</p>';
            if ( $debug_mode ) {
                echo '<p>' . esc_html__( 'Debug info:', 'dotmavriq-job-sync') . ' ' . esc_html( print_r( $jobs, true ) ) . '</p>';
            }
            echo '</div>';
            return;
        }

        if ( empty( $jobs['data'] ) ) {
            echo '<div class="teamtailor-notice teamtailor-notice-warning">';
            echo '<p>' . esc_html__( 'No jobs found in TeamTailor API response. The API connected successfully but no job listings were returned.', 'dotmavriq-job-sync') . '</p>';
            echo '</div>';
            return;
        }

        if ( $debug_mode ) {
            echo '<div class="teamtailor-status-box">';
            echo '<p><strong>▶</strong> ' . esc_html__( 'Processing job data', 'dotmavriq-job-sync') . '</p>';
            echo '</div>';
        }

        $existing_ids  = $this->get_existing_job_ids();
        $company_name  = $this->api->get_company_name();
        $jobs_synced   = 0;
        $jobs_updated  = 0;
        $jobs_removed  = 0;
        $job_count     = count( $jobs['data'] );

        foreach ( $jobs['data'] as $job ) {
            $job_id    = $job['id'];
            $post_id   = $this->get_post_id_by_job_id( $job_id );
            $job_title = $job['attributes']['title'];
            $job_body  = $job['attributes']['body'];

            // Fetch additional data from TeamTailor.
            $departments_data  = $this->api->fetch_data( "jobs/$job_id/department" );
            $locations_data    = $this->api->fetch_data( "jobs/$job_id/locations" );
            $extracted_locations = $this->api->extract_locations( $locations_data );
            $role_name         = $this->api->get_role_name( $job_id );

            $post_content          = $job_body;
            $job_apply_iframe_url  = $job['links']['careersite-job-apply-iframe-url'] ?? '';

            if ( $job_apply_iframe_url ) {
                $post_content .= "\n\n<iframe src='" . esc_url( $job_apply_iframe_url ) . "' style='width: 100%; height: 800px' frameborder='0'></iframe>";
            }

            $post_data = array(
                'post_type'    => 'teamtailor_jobs',
                'post_title'   => $job_title,
                'post_content' => $post_content,
                'post_status'  => 'publish',
                'meta_input'   => array(
                    '_teamtailor_job_id'      => $job_id,
                    'teamtailor_departments'  => $this->api->extract_department_name( $departments_data ),
                    'teamtailor_locations'    => $extracted_locations['locations'],
                    'teamtailor_countries'    => $extracted_locations['countries'],
                    'teamtailor_roles'        => $role_name,
                    'teamtailor_company'      => $company_name,
                ),
            );

            if ( $post_id ) {
                $post_data['ID'] = $post_id;
                wp_update_post( $post_data );
                $jobs_updated++;
            } else {
                wp_insert_post( $post_data );
                $jobs_synced++;
            }

            $key = array_search( $job_id, $existing_ids, true );
            if ( false !== $key ) {
                unset( $existing_ids[ $key ] );
            }
        }

        // Remove posts that no longer exist in TeamTailor.
        foreach ( $existing_ids as $id ) {
            $post_id = $this->get_post_id_by_job_id( $id );
            if ( $post_id ) {
                wp_delete_post( $post_id, true );
                $jobs_removed++;
            }
        }

        // Build summary.
        $summary = array();

        if ( $jobs_synced > 0 ) {
            /* translators: %1$d: number of jobs imported, %2$s: job/jobs */
            $summary[] = sprintf( __( '<strong>%1$d</strong> new %2$s imported', 'dotmavriq-job-sync'), $jobs_synced, _n( 'job', 'jobs', $jobs_synced, 'dotmavriq-job-sync') );
        }

        if ( $jobs_updated > 0 ) {
            /* translators: %1$d: number of jobs updated, %2$s: job/jobs */
            $summary[] = sprintf( __( '<strong>%1$d</strong> existing %2$s updated', 'dotmavriq-job-sync'), $jobs_updated, _n( 'job', 'jobs', $jobs_updated, 'dotmavriq-job-sync') );
        }

        if ( $jobs_removed > 0 ) {
            /* translators: %1$d: number of jobs removed, %2$s: job/jobs */
            $summary[] = sprintf( __( '<strong>%1$d</strong> obsolete %2$s removed', 'dotmavriq-job-sync'), $jobs_removed, _n( 'job', 'jobs', $jobs_removed, 'dotmavriq-job-sync') );
        }

        if ( empty( $summary ) ) {
            $summary[] = __( 'No changes made', 'dotmavriq-job-sync');
        }

        echo '<div class="teamtailor-notice teamtailor-notice-success">';
        echo '<p><strong>' . esc_html__( 'Sync completed successfully!', 'dotmavriq-job-sync') . '</strong></p>';
        echo '<p>' . wp_kses_post( implode( ', ', $summary ) ) . '.</p>';
        echo '</div>';

        echo '<p><a href="' . esc_url( admin_url( 'edit.php?post_type=teamtailor_jobs' ) ) . '" class="button button-secondary">' . esc_html__( 'View All Jobs', 'dotmavriq-job-sync') . '</a></p>';
    }
}

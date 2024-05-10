<?php
/**
 * Public-facing functionality
 *
 * @since      1.0.0
 *
 * @package    TeamTailor_Integrator
 * @subpackage TeamTailor_Integrator/public
 */

/**
 * Public-facing functionality.
 *
 * Defines the plugin name, version, and hooks for the public-facing side.
 *
 * @package    TeamTailor_Integrator
 * @subpackage TeamTailor_Integrator/public
 * @author     Jonatan Jansson
 */
class TeamTailor_Integrator_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name    The name of this plugin.
     * @param    string    $version        The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name . '-public',
            plugin_dir_url(__FILE__) . '../assets/css/teamtailor-integrator-public.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Get unique meta values for a given meta key.
     *
     * @since    1.0.0
     * @param    string    $meta_key    The meta key.
     * @return   array                 The unique meta values.
     */
    private function get_unique_meta_values($meta_key) {
        global $wpdb;
        $meta_values = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
            LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = %s
            AND p.post_status = 'publish'
            AND p.post_type = 'teamtailor_jobs'
            ORDER BY pm.meta_value ASC
        ", $meta_key));

        // Filter out any empty values
        return array_filter($meta_values, function($value) {
            return !empty($value);
        });
    }

    /**
     * Jobs shortcode callback.
     *
     * @since    1.0.0
     * @param    array    $atts    The shortcode attributes.
     * @return   string            The shortcode output.
     */
    public function jobs_shortcode($atts) {
        global $wp;
        ob_start(); // Start output buffering

        // Dynamically fetch unique meta values for filters
        $unique_departments = $this->get_unique_meta_values('departments');
        $unique_locations = $this->get_unique_meta_values('locations');
        $unique_roles = $this->get_unique_meta_values('roles');

        // Display dropdown filters
        ?>
        <form action="<?php echo esc_url(home_url($wp->request)); ?>" method="get" class="teamtailor-filter-form">
            <select name="department">
                <option value="">All Departments</option>
                <?php foreach ($unique_departments as $department): ?>
                    <option value="<?php echo esc_attr($department); ?>" <?php selected(isset($_GET['department']) ? $_GET['department'] : null, $department); ?>><?php echo esc_html($department); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="location">
                <option value="">All Locations</option>
                <?php foreach ($unique_locations as $location): ?>
                    <option value="<?php echo esc_attr($location); ?>" <?php selected(isset($_GET['location']) ? $_GET['location'] : null, $location); ?>><?php echo esc_html($location); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="role">
                <option value="">All Roles</option>
                <?php foreach ($unique_roles as $role): ?>
                    <option value="<?php echo esc_attr($role); ?>" <?php selected(isset($_GET['role']) ? $_GET['role'] : null, $role); ?>><?php echo esc_html($role); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="submit" value="Filter">
        </form>
        <?php

        // Adjust your WP_Query arguments based on the filter selections
        $meta_query_args = []; // Initialize meta query arguments array
        if (!empty($_GET['department'])) {
            $meta_query_args[] = [
                'key' => 'departments',
                'value' => sanitize_text_field($_GET['department']),
                'compare' => '='
            ];
        }
        if (!empty($_GET['location'])) {
            $meta_query_args[] = [
                'key' => 'locations',
                'value' => sanitize_text_field($_GET['location']),
                'compare' => '='
            ];
        }
        if (!empty($_GET['role'])) {
            $meta_query_args[] = [
                'key' => 'roles',
                'value' => sanitize_text_field($_GET['role']),
                'compare' => '='
            ];
        }

        // Query for 'teamtailor_jobs' posts including the meta query for filtering
        $args = array(
            'post_type' => 'teamtailor_jobs',
            'posts_per_page' => -1,
            'meta_query' => $meta_query_args
        );
        $jobs_query = new WP_Query($args);

        // Check if we have posts
        if ($jobs_query->have_posts()) {
            echo '<div class="teamtailor-jobs-listing">';
            while ($jobs_query->have_posts()) {
                $jobs_query->the_post();
                $post_id = get_the_ID();
                echo '<div class="teamtailor-job">';
                echo '<h2>' . get_the_title() . '</h2>';
                
                // Display all custom fields in divs
                $custom_fields = get_post_custom($post_id);
                foreach ($custom_fields as $key => $value) {
                    if (substr($key, 0, 1) !== '_') { // Skip hidden custom fields
                        echo '<div class="teamtailor-job-meta"><strong>' . esc_html($key) . ':</strong> ' . esc_html($value[0]) . '</div>';
                    }
                }

                // Link to the individual post
                echo '<a href="' . get_permalink($post_id) . '" class="teamtailor-job-link">Read More</a>';
                echo '</div>'; // Close .teamtailor-job
            }
            echo '</div>'; // Close .teamtailor-jobs-listing
        } else {
            echo '<p>No job listings found.</p>';
        }

        // Reset post data
        wp_reset_postdata();

        // Return the buffer contents
        return ob_get_clean();
    }
}
<?php
/**
 * Plugin Name: TeamTailor Integrator
 * Description: Integration with TeamTailor for WordPress.
 * Version: 0.4.5
 * Author: Jonatan Jansson
 * URI: https://github.com/dotMavriQ/CoSwift
 */

// Borås: https://www.youtube.com/watch?v=JyAFbGCsJtY

// Ensure WordPress is loaded
defined('ABSPATH') or die('No script kiddies please!');

// Include other PHP files
include_once(__DIR__ . '/Button_Form_SaveToken.php');
include_once(__DIR__ . '/Button_TestAPI.php');

// Add plugin to the admin menu
function coswift_add_to_menu() {
    add_menu_page('CoSwift Settings', 'CoSwift', 'manage_options', 'coswift', 'coswift_settings_page');
}
add_action('admin_menu', 'coswift_add_to_menu');

function coswift_register_settings() {
    register_setting('coswift-settings-group', 'coswift_api_token');
}
add_action('admin_init', 'coswift_register_settings');
// 
function coswift_settings_page() {
    $next_sync = wp_next_scheduled('coswift_hourly_sync');
    ?>
    <div class="wrap">
            <h1>TeamTailor Integrator Settings</h1>

            <div class="postbox" style="padding-left: 1em">
                <h2><span>Settings</span></h2>
            <?php coswift_save_token_form(); ?>
            </div>
            
            <div class="postbox" style="padding-left: 1em">
                <h2><span>Test API Connection</span></h2>
                <div class="inside">
                    <form method="post">
                        <p>Press the button to test the API connection using the stored API key.</p>
                        <input type="hidden" name="action" value="test_api">
                        <input type="submit" class="button button-primary" value="Test API">
                    </form>
                    <?php
                    // Check if the test API call was made
                    $apiTestResult = '';
                    if (isset($_POST['action']) && $_POST['action'] == 'test_api') {
                        echo '<div style="margin-top: 20px;">';
                        echo '<p><strong>API Test Result:</strong></p>';
                        coswift_test_api_call();
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <div class="postbox" style="padding-left: 1em">
                <h2><span>Sync from TeamTailor</span></h2>
                <div class="inside">
                    <p>Synchronize job listings from TeamTailor.</p>

<?php if ($next_sync) : ?>
    <pre>Next automatic sync is in: <?php echo human_time_diff($next_sync); ?></pre>
<?php endif; ?>
                    <form method="post" action="">
                        <input type="hidden" name="action" value="sync_teamtailor">
                        <input type="submit" class="button button-primary" value="Sync from TeamTailor">

                        <?php
                        if (isset($_POST['action']) && $_POST['action'] == 'sync_teamtailor') {
                            echo '<div style="margin-top: 20px;">';
                            echo '<p><strong>Sync Result:</strong></p>';
                            coswift_sync();
                            echo '</div>';
                        }
                        ?>
                    </form>
                </div>
            </div>
        </div>
    <?php
}

function coswift_test_api_call() {
    // Removed header function call
    $api_key = get_option('coswift_api_token');
    if (!$api_key) {
        echo '<div>API Key is not set.</div>';
        return;
    }

    $url = "https://api.teamtailor.com/v1/jobs";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Token token={$api_key}",
        "X-Api-Version: 20210218",
        "Content-Type: application/json"
    ));

    $response = curl_exec($ch);
    if ($error = curl_error($ch)) {
        echo "<div>cURL Error: {$error}</div>";
    } else {
        $decodedResponse = json_decode($response, true); // Decode as an array

        // Loop through each job and fetch details for specific relationships
        foreach ($decodedResponse['data'] as &$job) {
            if (isset($job['id'])) {
                $jobId = $job['id'];
                $job['departments_data'] = fetchTeamtailorData($api_key, "jobs/$jobId/department");
                $job['locations_data'] = fetchTeamtailorData($api_key, "jobs/$jobId/locations");
                $job['roles_data'] = fetchTeamtailorData($api_key, "jobs/$jobId/role");
                $job['custom_field_values_data'] = fetchTeamtailorData($api_key, "jobs/$jobId/custom-field-values");
            }
        }

        // Print the modified response with additional relationship data
        $prettyResponse = json_encode($decodedResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); // Add JSON_UNESCAPED_UNICODE
        echo '<div id="coswift-api-response" style="white-space: pre; max-height: 400px; overflow-y: scroll; background-color: #f4f4f4; border: 1px solid #ddd; padding: 10px; margin-top: 15px;">';
        echo htmlspecialchars($prettyResponse, ENT_QUOTES, 'UTF-8'); // Ensure htmlspecialchars uses UTF-8
        echo '</div>';
    }

    curl_close($ch);
}

function fetchTeamtailorData($api_key, $endpoint) {
    $url = "https://api.teamtailor.com/v1/$endpoint";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Token token={$api_key}",
        "X-Api-Version: 20210218",
        "Content-Type: application/json"
    ));

    $response = curl_exec($ch);
    if (!curl_error($ch)) {
        curl_close($ch);
        return json_decode($response, true);
    } else {
        $error = curl_error($ch);
        curl_close($ch);
        return "Error: $error";
    }
}


function coswift_register_custom_post_type() {
    register_post_type('coswift_jobs', [
        'labels' => [
            'name' => 'Teamtailor Jobs',
            'singular_name' => 'TeamTailor Job'
        ],
        'public' => true,
        'has_archive' => true,
        'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
        'rewrite' => ['slug' => 'jobs'], // Rewrite the slug to 'jobs'
    ]);
}
add_action('init', 'coswift_register_custom_post_type');


function coswift_add_job_metaboxes() {
    add_meta_box(
        'coswift_job_details',           // Unique ID for the metabox
        'Job Details',                   // Title of the metabox
        'coswift_job_details_callback',  // Callback function
        'coswift_jobs',                  // Post type
        'normal',                        // Context (where on the screen)
        'high'                           // Priority (order on the screen)
    );
}
add_action('add_meta_boxes', 'coswift_add_job_metaboxes');

function coswift_job_details_callback($post) {
    // Add nonce for security and authentication
    wp_nonce_field(plugin_basename(__FILE__), 'coswift_job_nonce');
    
    // Retrieve the current values for your custom meta fields
    $coswift_job_id = get_post_meta($post->ID, '_coswift_job_id', true);
    $coswift_job_type = get_post_meta($post->ID, '_coswift_job_type', true);

    // Metabox HTML
    echo '<label for="coswift_job_id">Job ID:</label>';
    echo '<input type="text" id="coswift_job_id" name="coswift_job_id" value="' . esc_attr($coswift_job_id) . '" size="25" />';

    echo '<label for="coswift_job_type">Job Type:</label>';
    echo '<input type="text" id="coswift_job_type" name="coswift_job_type" value="' . esc_attr($coswift_job_type) . '" size="25" />';
    echo '<label for="company">Company:</label>';
    echo '<input type="text" id="company" name="company" value="' . esc_attr(get_post_meta($post->ID, 'company', true)) . '" size="25" />';

}
function coswift_save_job_metaboxes($post_id) {
    if (!isset($_POST['coswift_job_nonce']) || !wp_verify_nonce($_POST['coswift_job_nonce'], plugin_basename(__FILE__))) {
        return $post_id;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }
    if ('coswift_jobs' != $_POST['post_type'] || !current_user_can('edit_post', $post_id)) {
        return $post_id;
    }
    if (isset($_POST['coswift_job_id'])) {
        update_post_meta($post_id, '_coswift_job_id', sanitize_text_field($_POST['coswift_job_id']));
    }
    if (isset($_POST['coswift_job_type'])) {
        update_post_meta($post_id, '_coswift_job_type', sanitize_text_field($_POST['coswift_job_type']));
    }
    if (isset($_POST['company'])) {
        update_post_meta($post_id, 'company', sanitize_text_field($_POST['company']));
    }
}
add_action('save_post', 'coswift_save_job_metaboxes');

function coswift_jobs_add_id_column( $columns ) {
    $columns['job_id'] = 'Job ID';
    return $columns;
}
add_filter( 'manage_coswift_jobs_posts_columns', 'coswift_jobs_add_id_column' );

function coswift_jobs_id_column_content( $column_name, $post_id ) {
    if ( 'job_id' == $column_name ) {
        $job_id = get_post_meta( $post_id, '_coswift_job_id', true );
        echo esc_html($job_id);
    }
}
add_action( 'manage_coswift_jobs_posts_custom_column', 'coswift_jobs_id_column_content', 10, 2 );

function coswift_jobs_add_company_column($columns) {
    $columns['company'] = 'Company';
    return $columns;
}
add_filter('manage_coswift_jobs_posts_columns', 'coswift_jobs_add_company_column');

function coswift_jobs_company_column_content($column_name, $post_id) {
    if ($column_name == 'company') {
        $company = get_post_meta($post_id, 'company', true);
        echo esc_html($company);
    }
}
add_action('manage_coswift_jobs_posts_custom_column', 'coswift_jobs_company_column_content', 10, 2);


function coswift_jobs_columns_order( $columns ) {
    $new_order = [];
    foreach($columns as $key => $value) {
        if ($key == 'title') {
            $new_order[$key] = $value;
            $new_order['job_id'] = 'Job ID';
        } else if ($key != 'date') {
            $new_order[$key] = $value;
        }
    }
    $new_order['date'] = 'Date';
    return $new_order;
    
}
add_filter( 'manage_coswift_jobs_posts_columns', 'coswift_jobs_columns_order', 15 );
function coswift_jobs_shortcode($atts) {
    global $wp;
    ob_start(); // Start output buffering

    // Dynamically fetch unique meta values for filters
    $unique_departments = get_unique_meta_values('departments');
    $unique_locations = get_unique_meta_values('locations');
    $unique_roles = get_unique_meta_values('roles');
    $unique_countries = get_unique_meta_values('countries');
    $unique_company = get_unique_meta_values('company');
    $unique_tt_custom_data = get_unique_meta_values('tt_custom_data');

    // Display dropdown filters
    ?>
    <form action="<?php echo esc_url(home_url($wp->request)); ?>" method="get">
    <?php if (coswift_field_is_active('departments')) : ?>
        <select name="department">
            <option value="">All Departments</option>
            <?php foreach ($unique_departments as $department): ?>
                <option value="<?php echo esc_attr($department); ?>" <?php selected(isset($_GET['department']) ? $_GET['department'] : null, $department); ?>><?php echo esc_html($department); ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <?php if (coswift_field_is_active('locations')) : ?>
        <select name="location">
            <option value="">All Locations</option>
            <?php foreach ($unique_locations as $location): ?>
                <option value="<?php echo esc_attr($location); ?>" <?php selected(isset($_GET['location']) ? $_GET['location'] : null, $location); ?>><?php echo esc_html($location); ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <?php if (coswift_field_is_active('country')) : ?>
        <select name="countries">
            <option value="">All Countries</option>
            <?php foreach ($unique_countries as $country): ?>
                <option value="<?php echo esc_attr($country); ?>" <?php selected(isset($_GET['countries']) ? $_GET['countries'] : null, $country); ?>><?php echo esc_html($country); ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <?php if (coswift_field_is_active('company')) : ?>
        <select name="location">
            <option value="">All Organisations</option>
            <?php foreach ($unique_company as $company): ?>
                <option value="<?php echo esc_attr($company); ?>" <?php selected(isset($_GET['company']) ? $_GET['company'] : null, $company); ?>><?php echo esc_html($company); ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <?php if (coswift_field_is_active('roles')) : ?>
        <select name="role">
            <option value="">All Roles</option>
            <?php foreach ($unique_roles as $role): ?>
                <option value="<?php echo esc_attr($role); ?>" <?php selected(isset($_GET['role']) ? $_GET['role'] : null, $role); ?>><?php echo esc_html($role); ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
    <?php if (coswift_field_is_active('tt_custom')) : ?>
        <select name="tt_custom">
            <option value="">All <?php echo get_option('coswift_custom_field_name') ? get_option('coswift_custom_field_name') : 'Custom'; ?> </option>
            <?php foreach ($unique_tt_custom_data as $c): ?>
                <option value="<?php echo esc_attr($c); ?>" <?php selected(isset($_GET['tt_custom']) ? $_GET['tt_custom'] : null, $c); ?>><?php echo esc_html($c); ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
        <input type="submit" value="Filter">
            <!--- m ---> 
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

    // Query for 'coswift_jobs' posts including the meta query for filtering
    $args = array(
        'post_type' => 'coswift_jobs',
        'posts_per_page' => -1,
        'meta_query' => $meta_query_args
    );
    $jobs_query = new WP_Query($args);

    // The rest of your existing shortcode logic...
    // Check if we have posts
    if ($jobs_query->have_posts()) {
        echo '<div class="teamtailor-jobs-listing">';
        while ($jobs_query->have_posts()) {
            $jobs_query->the_post();
            $post_id = get_the_ID();
            echo '<div class="teamtailor-job">';
            echo '<h3>' . get_the_title() . '</h3>';
            
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
        echo '<span class="teamtailor-nojobs">No job listings found.</span>';
    }

    // Reset post data
    wp_reset_postdata();

    // Return the buffer contents
    return ob_get_clean(); // Return the buffer contents
}
add_shortcode('coswiftjobs', 'coswift_jobs_shortcode');


function coswift_sync() {
    require __DIR__ . '/Button_SyncFromTeamTailor.php';
}
add_action('coswift_hourly_sync', 'coswift_sync');


add_action('init', function () {
    if (!wp_next_scheduled('coswift_hourly_sync')) {
        wp_schedule_event(time(), 'hourly', 'coswift_hourly_sync');
    }
});


function coswift_field_is_active($key) {
    $values = maybe_unserialize(get_option('coswift_fields', []));
    if (!is_array( $values)) {
        return false;
    }
    return in_array($key, $values);
}


// ACF Override
add_action('init', function() {
    // Check if ACF is active
    if (function_exists('acf_add_local_field_group')) {

        // Define the ACF field group
        acf_add_local_field_group(array(
            'key' => 'group_coswift_jobs',
            'title' => 'TeamTailor Jobs Fields',
            'fields' => array(
                array(
                    'key' => 'field_coswift_job_id',
                    'label' => 'Job ID',
                    'name' => '_coswift_job_id',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_coswift_departments',
                    'label' => 'Departments',
                    'name' => 'departments',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_coswift_locations',
                    'label' => 'Locations',
                    'name' => 'locations',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_coswift_roles',
                    'label' => 'Roles',
                    'name' => 'roles',
                    'type' => 'text',
                ),
                // Simplified Countries field
                array(
                    'key' => 'field_coswift_countries',
                    'label' => 'Countries',
                    'name' => 'countries',
                    'type' => 'text',
                ),

                array(
                    'key' => 'field_coswift_tt_custom_data',
                    'label' => 'TT Custom Data',
                    'name' => 'tt_custom_data',
                    'type' => 'text',
                )
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'coswift_jobs',
                    ),
                ),
            ),
        ));
    }
});



// Register with Elementor
add_action('elementor_pro/init', function() {
    if ( ! function_exists('ElementorPro\Modules\DynamicTags\Module::instance') ) {
        return;
    }

    $dynamic_tags = ElementorPro\Modules\DynamicTags\Module::instance();

    // Function to register the custom fields as dynamic tags
    $register_custom_field = function($field_key, $field_label) use ($dynamic_tags) {
        $dynamic_tags->register_tag(new class($field_key, $field_label) extends \ElementorPro\Modules\DynamicTags\Tags\Base_Data_Tag {
            private $field_key;
            private $field_label;

            public function __construct($field_key, $field_label) {
                $this->field_key = $field_key;
                $this->field_label = $field_label;
            }

            public function get_name() {
                return 'coswift_job_' . $this->field_key;
            }

            public function get_title() {
                return __($this->field_label, 'text-domain');
            }

            public function get_categories() {
                return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
            }

            public

function get_value(array $options = []) {
global $post;
return get_post_meta($post->ID, $this->field_key, true);
}
});
};

// Register each custom field
$register_custom_field('_coswift_job_id', 'TeamTailor Job ID');
$register_custom_field('departments', 'TeamTailor Departments');
$register_custom_field('locations', 'TeamTailor Locations');
$register_custom_field('roles', 'TeamTailor Roles');
$register_custom_field('tt_custom_data', 'TeamTailor Custom Data');

});
function get_unique_meta_values($meta_key) {
    global $wpdb;
    $meta_values = $wpdb->get_col($wpdb->prepare("
        SELECT DISTINCT pm.meta_value FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key = %s
        AND p.post_status = 'publish'
        AND p.post_type = 'coswift_jobs'
        ORDER BY pm.meta_value ASC
    ", $meta_key));

    // Filter out any empty values
    return array_filter($meta_values, function($value) {
        return !empty($value);
    });
}
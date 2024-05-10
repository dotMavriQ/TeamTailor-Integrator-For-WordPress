<?php
/**
 * Admin-specific functionality
 *
 * @since      1.0.0
 *
 * @package    TeamTailor_Integrator
 * @subpackage TeamTailor_Integrator/admin
 */

/**
 * Admin-specific functionality.
 *
 * Defines the plugin name, version, and hooks for the admin area.
 *
 * @package    TeamTailor_Integrator
 * @subpackage TeamTailor_Integrator/admin
 * @author     Jonatan Jansson
 */
class TeamTailor_Integrator_Admin {

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
        
        // Register AJAX handler for synchronization
        add_action('wp_ajax_teamtailor_sync_jobs', array($this, 'ajax_sync_jobs'));
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        $screen = get_current_screen();
        
        // Only load on our plugin pages
        if (!isset($screen->id) || strpos($screen->id, 'teamtailor-integrator') === false) {
            return;
        }
        
        wp_enqueue_style(
            $this->plugin_name . '-admin',
            plugin_dir_url(__FILE__) . '../assets/css/teamtailor-integrator-admin.css',
            array(),
            $this->version,
            'all'
        );
        
        // Enqueue Prism.js CSS for syntax highlighting
        wp_enqueue_style(
            $this->plugin_name . '-prism',
            plugin_dir_url(__FILE__) . '../assets/css/prism-okaidia.css',
            array(),
            $this->version,
            'all'
        );
    }
    
    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        $screen = get_current_screen();
        
        // Only load on our plugin pages
        if (!isset($screen->id) || strpos($screen->id, 'teamtailor-integrator') === false) {
            return;
        }
        
        // Enqueue our admin script
        wp_enqueue_script(
            $this->plugin_name . '-admin',
            plugin_dir_url(__FILE__) . '../assets/js/teamtailor-integrator-admin.js',
            array('jquery'),
            $this->version,
            true
        );
        
        // Enqueue Prism.js for syntax highlighting
        wp_enqueue_script(
            $this->plugin_name . '-prism',
            plugin_dir_url(__FILE__) . '../assets/js/prism.js',
            array(),
            $this->version,
            true
        );
    }

    /**
     * Register admin menu items
     *
     * @since    1.0.0
     */
    public function add_admin_menu() {
        add_menu_page(
            'TeamTailor Integrator',
            'TeamTailor Integrator',
            'manage_options',
            'teamtailor-integrator',
            array($this, 'display_settings_page'),
            'dashicons-businessman',
            26
        );
    }

    /**
     * Register settings for the plugin
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting('teamtailor-integrator-settings-group', 'teamtailor_integrator_api_token');
        register_setting('teamtailor-integrator-settings-group', 'teamtailor_integrator_debug_mode');
    }

    /**
     * Display the settings page
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        // Get job stats
        $total_jobs = wp_count_posts('teamtailor_jobs')->publish;
        $draft_jobs = wp_count_posts('teamtailor_jobs')->draft;
        
        // Get latest sync time
        $last_sync = get_option('teamtailor_last_sync_time', false);
        $last_sync_text = $last_sync ? human_time_diff($last_sync, current_time('timestamp')) . ' ago' : 'Never';

        // Check if API key is set
        $api_key = get_option('teamtailor_integrator_api_token');
        $api_connected = !empty($api_key);
        
        ?>
        <div class="wrap teamtailor-admin-wrap">
            <!-- Header -->
            <div class="teamtailor-header">
                <img src="<?php echo plugin_dir_url(dirname(__FILE__)) . 'assets/images/TTWapuu.png'; ?>" alt="TeamTailor Integrator" class="teamtailor-logo">
                <div>
                    <h1><?php echo esc_html(get_admin_page_title()); ?> 
                        <span class="teamtailor-header-version">v<?php echo esc_html($this->version); ?></span>
                    </h1>
                </div>
            </div>
            
            <!-- Dashboard Grid Layout -->
            <div class="teamtailor-dashboard-grid">
                <!-- Main Column -->
                <div class="teamtailor-column-main">
                    
                    <!-- Tabs Navigation -->
                    <div class="teamtailor-tabs">
                        <div class="teamtailor-tabs-list">
                            <a href="#" class="teamtailor-tab" data-tab="tab-settings">Settings</a>
                            <a href="#" class="teamtailor-tab" data-tab="tab-api">API Test</a>
                            <a href="#" class="teamtailor-tab" data-tab="tab-advanced">Advanced</a>
                        </div>
                        
                        <!-- Settings Tab -->
                        <div id="tab-settings" class="teamtailor-tab-content">
                            <div class="teamtailor-panel">
                                <h2>API Configuration</h2>
                                <p class="teamtailor-panel-intro">
                                    To connect with TeamTailor, you need to provide your TeamTailor API token.
                                    You can find this in your TeamTailor account under Settings &gt; Integrations &gt; API.
                                </p>
                                
                                <?php $this->save_token_form(); ?>
                            </div>
                            
                            <?php if ($api_connected): ?>
                            <div class="teamtailor-panel">
                                <h2>Synchronization</h2>
                                <p class="teamtailor-panel-intro">
                                    Sync your TeamTailor job listings to WordPress. This will import all active job listings
                                    and update any existing ones.
                                </p>
                                
                                <div class="teamtailor-status-box">
                                    <p><strong>Last Sync:</strong> <?php echo esc_html($last_sync_text); ?></p>
                                </div>
                                
                                <form method="post" action="">
                                    <?php wp_nonce_field('teamtailor_sync_action', 'teamtailor_sync_nonce'); ?>
                                    <input type="submit" id="teamtailor-sync-btn" name="sync_teamtailor" class="button button-primary" value="Sync from TeamTailor" data-loading-text="Syncing...">
                                </form>
                                
                                <?php
                                // Check if sync button was clicked
                                if (isset($_POST['sync_teamtailor'])) {
                                    // Verify nonce for security
                                    if (isset($_POST['teamtailor_sync_nonce']) && wp_verify_nonce($_POST['teamtailor_sync_nonce'], 'teamtailor_sync_action')) {
                                        // Debugging - only show if debug mode is enabled
                                        if (get_option('teamtailor_integrator_debug_mode')) {
                                            echo '<div class="teamtailor-status-box">';
                                            echo '<p><strong>▶</strong> Sync initiated</p>';
                                            echo '</div>';
                                        }
                                        
                                        // Load sync class
                                        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-teamtailor-integrator-sync.php';
                                        $sync = new TeamTailor_Integrator_Sync();
                                        $sync->sync_teamtailor();
                                        
                                        // Update last sync time
                                        update_option('teamtailor_last_sync_time', current_time('timestamp'));
                                    } else {
                                        echo '<div class="teamtailor-notice teamtailor-notice-error">';
                                        echo '<p>Security verification failed. Please try again.</p>';
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- API Test Tab -->
                        <div id="tab-api" class="teamtailor-tab-content">
                            <div class="teamtailor-panel">
                                <h2>API Test</h2>
                                <p class="teamtailor-panel-intro">
                                    Test your TeamTailor API connection and view the raw JSON data returned by the API.
                                    This helps you understand the structure of the data that will be imported.
                                </p>
                                
                                
                                <?php if (!$api_connected): ?>
                                    <div class="teamtailor-notice teamtailor-notice-warning">
                                        <p>You need to set up your API token in the Settings tab before testing the API.</p>
                                    </div>
                                <?php else: ?>
                                    <button id="teamtailor-test-api-btn" class="button button-primary">Test API</button>
                                    
                                    <div id="teamtailor-api-response" style="margin-top: 20px;">
                                        <!-- API Response will be loaded here via AJAX -->
                                    </div>
                                    
                                    <script>
                                        jQuery(document).ready(function($) {
                                            $('#teamtailor-test-api-btn').on('click', function(e) {
                                                e.preventDefault();
                                                
                                                // Show loading indicator
                                                $('#teamtailor-api-response').html('<div class="teamtailor-loading-message">Loading API response...</div>');
                                                
                                                // Make AJAX request to test API
                                                $.ajax({
                                                    url: ajaxurl,
                                                    type: 'POST',
                                                    data: {
                                                        action: 'teamtailor_test_api',
                                                        nonce: '<?php echo wp_create_nonce('teamtailor_test_api_nonce'); ?>'
                                                    },
                                                    success: function(response) {
                                                        $('#teamtailor-api-response').html(response);
                                                        
                                                        // Initialize Prism.js highlighting after content is loaded
                                                        if (typeof Prism !== 'undefined') {
                                                            Prism.highlightAll();
                                                        }
                                                        
                                                        // Reset button loading state
                                                        $('#teamtailor-test-api-btn').removeClass('teamtailor-loading').prop('disabled', false);
                                                    },
                                                    error: function() {
                                                        $('#teamtailor-api-response').html('<div class="teamtailor-notice teamtailor-notice-error"><p>Error: Failed to fetch API response. Please try again.</p></div>');
                                                        // Also reset button loading state on error
                                                        $('#teamtailor-test-api-btn').removeClass('teamtailor-loading').prop('disabled', false);
                                                    },
                                                    complete: function() {
                                                        // Final safety check to ensure button is reset
                                                        setTimeout(function() {
                                                            $('#teamtailor-test-api-btn').removeClass('teamtailor-loading').prop('disabled', false);
                                                        }, 500);
                                                    }
                                                });
                                            });
                                        });
                                    </script>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Advanced Tab -->
                        <div id="tab-advanced" class="teamtailor-tab-content">
                            <div class="teamtailor-panel">
                                <h2>Advanced Settings</h2>
                                <p class="teamtailor-panel-intro">
                                    These advanced settings allow you to customize the behavior of the TeamTailor Integrator plugin.
                                </p>
                                
                                <form method="post" action="options.php">
                                    <?php settings_fields('teamtailor-integrator-settings-group'); ?>
                                    
                                    <div class="teamtailor-settings-checkbox">
                                        <label>
                                            <input 
                                                type="checkbox" 
                                                name="teamtailor_integrator_debug_mode" 
                                                value="1" 
                                                <?php checked(1, get_option('teamtailor_integrator_debug_mode'), true); ?>
                                            /> 
                                            <strong>Debugging</strong> - Show detailed debug information during sync
                                        </label>
                                    </div>
                                    
                                    <!-- Preserve API token when saving debug settings -->
                                    <input type="hidden" name="teamtailor_integrator_api_token" value="<?php echo esc_attr(get_option('teamtailor_integrator_api_token')); ?>" />
                                    
                                    <p>
                                        <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Advanced Settings">
                                    </p>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="teamtailor-column-sidebar">
                    <!-- Help Box -->
                    <div class="teamtailor-help-box">
                        <h3>Quick Help</h3>
                        <ul>
                            <li><strong>API Token:</strong> Required to connect to TeamTailor</li>
                            <li><strong>Sync:</strong> Import and update jobs from TeamTailor</li>
                            <li><strong>Shortcode:</strong> Use <code>[teamtailor_jobs]</code> to display jobs</li>
                        </ul>
                    </div>
                    
                    <!-- API Info Box -->
                    <div class="teamtailor-help-box">
                        <h3>About the API Test</h3>
                        <p>The API Test helps you verify that your TeamTailor connection is working correctly. Additionally:</p>
                        <ul>
                            <li>See the exact format of data from TeamTailor</li>
                            <li>Verify job listings are available</li>
                            <li>Understand the data structure before import</li>
                        </ul>
                    </div>
                    
                    <!-- Documentation Box -->
                    <div class="teamtailor-card">
                        <div class="teamtailor-card-header">
                            <h3 class="teamtailor-card-title">Documentation</h3>
                        </div>
                        <div class="teamtailor-card-body">
                            <div class="teamtailor-buttons-group">
                                <p>For TeamTailor API documentation, visit:</p>
                                <a href="https://docs.teamtailor.com/" target="_blank" class="button button-secondary">
                                    TeamTailor API Docs
                                </a>
                                
                                <p style="margin-top: 15px;">See our plugin documentation and keep up with updates:</p>
                                <a href="https://github.com/dotmavriq/TeamTailor-Integrator-For-WordPress" target="_blank" class="button button-secondary">
                                    GitHub Repository
                                </a>
                                
                                <p style="margin-top: 15px;">Request features or report bugs:</p>
                                <a href="https://github.com/dotmavriq/TeamTailor-Integrator-For-WordPress/issues" target="_blank" class="button button-secondary">
                                    GitHub Issues
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * API token form
     *
     * @since    1.0.0
     */
    public function save_token_form() {
        // Check if the form has been submitted
        if (isset($_POST['teamtailor_integrator_api_token'])) {
            $api_token = sanitize_text_field($_POST['teamtailor_integrator_api_token']);

            // Validate the API token
            if (!empty($api_token)) {
                // Save the token
                update_option('teamtailor_integrator_api_token', $api_token);
                echo '<div class="teamtailor-notice teamtailor-notice-success"><p>API Token saved successfully.</p></div>';
            } else {
                // Display error message
                echo '<div class="teamtailor-notice teamtailor-notice-error"><p>API Token cannot be empty. Please check your token and try again.</p></div>';
            }
        }

        // Get connection status
        $api_key = get_option('teamtailor_integrator_api_token');
        $has_key = !empty($api_key);

        // Connection status indicator
        if ($has_key) {
            echo '<div class="teamtailor-status-box teamtailor-status-success">';
            echo '<p><strong>Status:</strong> Connected to TeamTailor API</p>';
            echo '</div>';
        } else {
            echo '<div class="teamtailor-status-box teamtailor-status-warning">';
            echo '<p><strong>Status:</strong> Not connected - API token required</p>';
            echo '</div>';
        }

        // Form HTML
        ?>
        <div class="teamtailor-token-form">
            <form method="post" action="">
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">API Token:</th>
                        <td>
                            <input 
                                type="text" 
                                name="teamtailor_integrator_api_token" 
                                value="<?php echo esc_attr(get_option('teamtailor_integrator_api_token')); ?>" 
                                placeholder="Enter your TeamTailor API token" 
                            />
                            <p class="description">
                                Enter your TeamTailor API token here.
                                <a href="https://docs.teamtailor.com/" target="_blank">Learn how to get your API token</a>
                            </p>
                        </td>
                    </tr>
                </table>
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Save API Token">
            </form>
        </div>
        <?php
    }

    /**
     * Test the API connection
     *
     * @since    1.0.0
     */
    public function test_api_call() {
        $api_key = get_option('teamtailor_integrator_api_token');
        $debug_mode = get_option('teamtailor_integrator_debug_mode');
        
        if (!$api_key) {
            echo '<div class="teamtailor-notice teamtailor-notice-error">';
            echo '<p>API Key is not set. Please configure your API token in the Settings tab.</p>';
            echo '</div>';
            return;
        }

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-teamtailor-integrator-api.php';
        $api = new TeamTailor_Integrator_API($api_key, $debug_mode);
        $response = $api->get_jobs();
        
        if ($response === false) {
            echo '<div class="teamtailor-notice teamtailor-notice-error">';
            echo '<p>Connection failed: Unable to connect to TeamTailor API. Please check your API token and try again.</p>';
            echo '</div>';
        } else {
            // Get a count of jobs
            $job_count = count($response['data'] ?? []);
            
            echo '<div class="teamtailor-notice teamtailor-notice-success">';
            echo '<p>Connection successful! Received data for ' . $job_count . ' job listings.</p>';
            echo '</div>';
            
            // Format the response as pretty JSON
            $prettyResponse = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            
            // Always show the JSON response
            ?>
            <div class="teamtailor-json-container">
                <div class="teamtailor-json-header">
                    <h3>Raw JSON Response</h3>
                    <button id="teamtailor-copy-json" class="button" data-clipboard-target="#teamtailor-json-code">
                        Copy JSON
                    </button>
                </div>
                
                <pre class="language-json"><code id="teamtailor-json-code" class="language-json"><?php echo htmlspecialchars($prettyResponse, ENT_QUOTES, 'UTF-8'); ?></code></pre>
            </div>
            <?php
        }
    }
    
    /**
     * AJAX handler for the API test
     *
     * @since    1.0.0
     */
    public function ajax_test_api() {
        // Check nonce for security
        check_ajax_referer('teamtailor_test_api_nonce', 'nonce');
        
        // Only allow admin users
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        // Output buffer to capture the test_api_call output
        ob_start();
        $this->test_api_call();
        $output = ob_get_clean();
        
        // Send the formatted output
        echo $output;
        wp_die();
    }

    /**
     * Register custom post type for jobs
     *
     * @since    1.0.0
     */
    public function register_custom_post_type() {
        register_post_type('teamtailor_jobs', [
            'labels' => [
                'name' => 'TeamTailor Jobs',
                'singular_name' => 'TeamTailor Job',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Job',
                'edit_item' => 'Edit Job',
                'new_item' => 'New Job',
                'view_item' => 'View Job',
                'search_items' => 'Search Jobs',
                'not_found' => 'No jobs found',
                'not_found_in_trash' => 'No jobs found in Trash',
                'all_items' => 'All Jobs',
                'archives' => 'Job Archives',
                'insert_into_item' => 'Insert into job',
                'uploaded_to_this_item' => 'Uploaded to this job',
                'featured_image' => 'Job Image',
                'set_featured_image' => 'Set job image',
                'remove_featured_image' => 'Remove job image',
                'use_featured_image' => 'Use as job image',
                'filter_items_list' => 'Filter jobs list',
                'items_list_navigation' => 'Jobs list navigation',
                'items_list' => 'Jobs list',
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'rewrite' => ['slug' => 'jobs'],
            'menu_icon' => 'dashicons-businessman',
            'show_in_rest' => true,
        ]);
    }

    /**
     * Add metaboxes to the job post type
     *
     * @since    1.0.0
     */
    public function add_job_metaboxes() {
        add_meta_box(
            'teamtailor_job_details',
            'Job Details',
            array($this, 'job_details_callback'),
            'teamtailor_jobs',
            'normal',
            'high'
        );
    }

    /**
     * Render the job details metabox
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function job_details_callback($post) {
        // Add nonce for security and authentication
        wp_nonce_field(plugin_basename(__FILE__), 'teamtailor_job_nonce');
        
        // Retrieve the current values for your custom meta fields
        $teamtailor_job_id = get_post_meta($post->ID, '_teamtailor_job_id', true);
        $teamtailor_job_type = get_post_meta($post->ID, '_teamtailor_job_type', true);

        // Metabox HTML
        echo '<label for="teamtailor_job_id">Job ID:</label>';
        echo '<input type="text" id="teamtailor_job_id" name="teamtailor_job_id" value="' . esc_attr($teamtailor_job_id) . '" size="25" />';

        echo '<label for="teamtailor_job_type">Job Type:</label>';
        echo '<input type="text" id="teamtailor_job_type" name="teamtailor_job_type" value="' . esc_attr($teamtailor_job_type) . '" size="25" />';
        
        echo '<label for="company">Company:</label>';
        echo '<input type="text" id="company" name="company" value="' . esc_attr(get_post_meta($post->ID, 'company', true)) . '" size="25" />';
    }

    /**
     * Save the metabox data
     *
     * @since    1.0.0
     * @param    int    $post_id    The ID of the post being saved.
     */
    public function save_job_metaboxes($post_id) {
        if (!isset($_POST['teamtailor_job_nonce']) || !wp_verify_nonce($_POST['teamtailor_job_nonce'], plugin_basename(__FILE__))) {
            return $post_id;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }
        if ('teamtailor_jobs' != $_POST['post_type'] || !current_user_can('edit_post', $post_id)) {
            return $post_id;
        }
        if (isset($_POST['teamtailor_job_id'])) {
            update_post_meta($post_id, '_teamtailor_job_id', sanitize_text_field($_POST['teamtailor_job_id']));
        }
        if (isset($_POST['teamtailor_job_type'])) {
            update_post_meta($post_id, '_teamtailor_job_type', sanitize_text_field($_POST['teamtailor_job_type']));
        }
        if (isset($_POST['company'])) {
            update_post_meta($post_id, 'company', sanitize_text_field($_POST['company']));
        }
    }

    /**
     * Add ID column to the jobs list
     *
     * @since    1.0.0
     * @param    array    $columns    The columns.
     * @return   array                The modified columns.
     */
    public function jobs_add_id_column($columns) {
        $columns['job_id'] = 'Job ID';
        return $columns;
    }

    /**
     * Display the job ID in the column
     *
     * @since    1.0.0
     * @param    string    $column_name    The column name.
     * @param    int       $post_id        The post ID.
     */
    public function jobs_id_column_content($column_name, $post_id) {
        if ('job_id' == $column_name) {
            $job_id = get_post_meta($post_id, '_teamtailor_job_id', true);
            echo esc_html($job_id);
        }
    }

    /**
     * Add company column to the jobs list
     *
     * @since    1.0.0
     * @param    array    $columns    The columns.
     * @return   array                The modified columns.
     */
    public function jobs_add_company_column($columns) {
        $columns['company'] = 'Company';
        return $columns;
    }

    /**
     * Display the company in the column
     *
     * @since    1.0.0
     * @param    string    $column_name    The column name.
     * @param    int       $post_id        The post ID.
     */
    public function jobs_company_column_content($column_name, $post_id) {
        if ($column_name == 'company') {
            $company = get_post_meta($post_id, 'company', true);
            echo esc_html($company);
        }
    }
    
    /**
     * AJAX handler for job synchronization
     *
     * @since    1.0.0
     */
    public function ajax_sync_jobs() {
        // Check nonce for security
        check_ajax_referer('teamtailor_sync_action', 'teamtailor_sync_nonce');
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        // Start output buffer to capture all output
        ob_start();
        
        // Load sync class
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-teamtailor-integrator-sync.php';
        $sync = new TeamTailor_Integrator_Sync();
        $sync->sync_teamtailor();
        
        // Update last sync time
        update_option('teamtailor_last_sync_time', current_time('timestamp'));
        
        // Get buffer contents
        $output = ob_get_clean();
        
        // Send response
        echo $output;
        wp_die();
    }

    /**
     * Reorder the columns in the jobs list
     *
     * @since    1.0.0
     * @param    array    $columns    The columns.
     * @return   array                The reordered columns.
     */
    public function jobs_columns_order($columns) {
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

    /**
     * Register ACF field group if ACF is active
     *
     * @since    1.0.0
     */
    public function register_acf_fields() {
        if (function_exists('acf_add_local_field_group')) {
            acf_add_local_field_group(array(
                'key' => 'group_teamtailor_jobs',
                'title' => 'TeamTailor Jobs Fields',
                'fields' => array(
                    array(
                        'key' => 'field_teamtailor_job_id',
                        'label' => 'Job ID',
                        'name' => '_teamtailor_job_id',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_teamtailor_departments',
                        'label' => 'Departments',
                        'name' => 'departments',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_teamtailor_locations',
                        'label' => 'Locations',
                        'name' => 'locations',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_teamtailor_roles',
                        'label' => 'Roles',
                        'name' => 'roles',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_teamtailor_countries',
                        'label' => 'Countries',
                        'name' => 'countries',
                        'type' => 'text',
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'teamtailor_jobs',
                        ),
                    ),
                ),
            ));
        }
    }

    /**
     * Register Elementor dynamic tags if Elementor is active
     *
     * @since    1.0.0
     */
    public function register_elementor_dynamic_tags() {
        if (!function_exists('ElementorPro\Modules\DynamicTags\Module::instance')) {
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
                    parent::__construct();
                }

                public function get_name() {
                    return 'teamtailor_job_' . $this->field_key;
                }

                public function get_title() {
                    return __($this->field_label, 'teamtailor-integrator');
                }

                public function get_categories() {
                    return [ \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY ];
                }

                public function get_value(array $options = []) {
                    global $post;
                    return get_post_meta($post->ID, $this->field_key, true);
                }
            });
        };

        // Register each custom field
        $register_custom_field('_teamtailor_job_id', 'TeamTailor Job ID');
        $register_custom_field('departments', 'TeamTailor Departments');
        $register_custom_field('locations', 'TeamTailor Locations');
        $register_custom_field('roles', 'TeamTailor Roles');
    }
}
<?php
/**
 * Admin-specific functionality
 *
 * @since      1.0.0
 *
 * @package    DotMavriQ_Job_Sync
 * @subpackage DotMavriQ_Job_Sync/admin
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin-specific functionality.
 *
 * Defines the plugin name, version, and hooks for the admin area.
 *
 * @package    DotMavriQ_Job_Sync
 * @subpackage DotMavriQ_Job_Sync/admin
 * @author     Jonatan Jansson
 */
class DotMavriQ_Job_Sync_Admin {

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
        if (!isset($screen->id) || strpos($screen->id, 'dotmavriq-job-sync') === false) {
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
        if (!isset($screen->id) || strpos($screen->id, 'dotmavriq-job-sync') === false) {
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

        wp_localize_script(
            $this->plugin_name . '-admin',
            'teamtailorAdmin',
            array(
                'ajaxUrl'      => admin_url('admin-ajax.php'),
                'testApiNonce' => wp_create_nonce('teamtailor_test_api_nonce'),
                'i18nLoading'  => esc_html__('Loading API response...', 'dotmavriq-job-sync'),
                'i18nError'    => esc_html__('Error: Failed to fetch API response. Please try again.', 'dotmavriq-job-sync'),
            )
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
            esc_html__( "dotMavriQ's Job Sync for Teamtailor", 'dotmavriq-job-sync'),
            esc_html__( "dotMavriQ's Job Sync for Teamtailor", 'dotmavriq-job-sync'),
            'manage_options',
            'dotmavriq-job-sync',
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
        register_setting('teamtailor-integrator-settings-group', 'teamtailor_integrator_api_token', 'sanitize_text_field');
        register_setting('teamtailor-integrator-settings-group', 'teamtailor_integrator_debug_mode', 'absint');
        register_setting('teamtailor-integrator-settings-group', 'teamtailor_integrator_use_mock_data', 'absint');
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
        $last_sync_text = $last_sync ? sprintf(/* translators: %s: time since last sync */ esc_html__('%s ago', 'dotmavriq-job-sync'), human_time_diff($last_sync, current_time('timestamp'))) : esc_html__('Never', 'dotmavriq-job-sync');

        // Check if API key is set (or mock mode is active)
        $api_key = get_option('teamtailor_integrator_api_token');
        $use_mock = (bool) get_option('teamtailor_integrator_use_mock_data', false);
        $api_connected = !empty($api_key) || $use_mock;

        ?>
        <div class="wrap teamtailor-admin-wrap">
            <!-- Header -->
            <div class="teamtailor-header">
                <img src="<?php echo esc_url( plugin_dir_url(dirname(__FILE__)) . 'assets/images/TTWapuu.png' ); ?>" alt="TeamTailor Integrator" class="teamtailor-logo">
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
                            <a href="#" class="teamtailor-tab" data-tab="tab-settings"><?php esc_html_e('Settings', 'dotmavriq-job-sync'); ?></a>
                            <a href="#" class="teamtailor-tab" data-tab="tab-api"><?php esc_html_e('API Test', 'dotmavriq-job-sync'); ?></a>
                            <a href="#" class="teamtailor-tab" data-tab="tab-advanced"><?php esc_html_e('Advanced', 'dotmavriq-job-sync'); ?></a>
                        </div>

                        <!-- Settings Tab -->
                        <div id="tab-settings" class="teamtailor-tab-content">
                            <div class="teamtailor-panel">
                                <h2><?php esc_html_e('API Configuration', 'dotmavriq-job-sync'); ?></h2>
                                <p class="teamtailor-panel-intro">
                                    <?php esc_html_e('To connect with TeamTailor, you need to provide your TeamTailor API token. You can find this in your TeamTailor account under Settings > Integrations > API.', 'dotmavriq-job-sync'); ?>
                                </p>

                                <?php $this->save_token_form(); ?>
                            </div>

                            <?php if ($api_connected): ?>
                            <div class="teamtailor-panel">
                                <h2><?php esc_html_e('Synchronization', 'dotmavriq-job-sync'); ?></h2>
                                <p class="teamtailor-panel-intro">
                                    <?php esc_html_e('Sync your TeamTailor job listings to WordPress. This will import all active job listings and update any existing ones.', 'dotmavriq-job-sync'); ?>
                                </p>

                                <div class="teamtailor-status-box">
                                    <p><strong><?php esc_html_e('Last Sync:', 'dotmavriq-job-sync'); ?></strong> <?php echo esc_html($last_sync_text); ?></p>
                                </div>

                                <form method="post" action="">
                                    <?php wp_nonce_field('teamtailor_sync_action', 'teamtailor_sync_nonce'); ?>
                                    <input type="submit" id="teamtailor-sync-btn" name="sync_teamtailor" class="button button-primary" value="<?php esc_attr_e('Sync from TeamTailor', 'dotmavriq-job-sync'); ?>" data-loading-text="<?php echo esc_attr__('Syncing...', 'dotmavriq-job-sync'); ?>">
                                </form>

                                <?php
                                // Check if sync button was clicked
                                if (isset($_POST['sync_teamtailor'])) {
                                    // Verify nonce for security
                                    if (isset($_POST['teamtailor_sync_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['teamtailor_sync_nonce'])), 'teamtailor_sync_action')) {
                                        // Debugging - only show if debug mode is enabled
                                        if (get_option('teamtailor_integrator_debug_mode')) {
                                            echo '<div class="teamtailor-status-box">';
                                            echo '<p><strong>▶</strong> ' . esc_html__( 'Sync initiated', 'dotmavriq-job-sync') . '</p>';
                                            echo '</div>';
                                        }

                                        // Load sync class
                                        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-dotmavriq-job-sync-sync.php';
                                        $sync = new DotMavriQ_Job_Sync_Sync();
                                        $sync->sync_teamtailor();

                                        // Update last sync time
                                        update_option('teamtailor_last_sync_time', current_time('timestamp'));
                                    } else {
                                        echo '<div class="teamtailor-notice teamtailor-notice-error">';
                                        echo '<p>' . esc_html__( 'Security verification failed. Please try again.', 'dotmavriq-job-sync') . '</p>';
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
                                <h2><?php esc_html_e('API Test', 'dotmavriq-job-sync'); ?></h2>
                                <p class="teamtailor-panel-intro">
                                    <?php esc_html_e('Test your TeamTailor API connection and view the raw JSON data returned by the API. This helps you understand the structure of the data that will be imported.', 'dotmavriq-job-sync'); ?>
                                </p>


                                <?php if (!$api_connected): ?>
                                    <div class="teamtailor-notice teamtailor-notice-warning">
                                        <p><?php esc_html_e('You need to set up your API token in the Settings tab or enable "Use Mock Data" in the Advanced tab before testing the API.', 'dotmavriq-job-sync'); ?></p>
                                    </div>
                                <?php else: ?>
                                    <button id="teamtailor-test-api-btn" class="button button-primary"><?php esc_html_e('Test API', 'dotmavriq-job-sync'); ?></button>

                                    <div id="teamtailor-api-response" style="margin-top: 20px;">
                                        <!-- API Response will be loaded here via AJAX -->
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Advanced Tab -->
                        <div id="tab-advanced" class="teamtailor-tab-content">
                            <div class="teamtailor-panel">
                                <h2><?php esc_html_e('Advanced Settings', 'dotmavriq-job-sync'); ?></h2>
                                <p class="teamtailor-panel-intro">
                                    <?php esc_html_e('These advanced settings allow you to customize the behavior of the TeamTailor Integrator plugin.', 'dotmavriq-job-sync'); ?>
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
                                            <strong><?php esc_html_e('Debugging', 'dotmavriq-job-sync'); ?></strong> - <?php esc_html_e('Show detailed debug information during sync', 'dotmavriq-job-sync'); ?>
                                        </label>
                                    </div>

                                    <div class="teamtailor-settings-checkbox" style="margin-top: 12px;">
                                        <label>
                                            <input
                                                type="checkbox"
                                                name="teamtailor_integrator_use_mock_data"
                                                value="1"
                                                <?php checked(1, get_option('teamtailor_integrator_use_mock_data'), true); ?>
                                            />
                                            <strong><?php esc_html_e('Use Mock Data', 'dotmavriq-job-sync'); ?></strong> - <?php esc_html_e('Use simulated job data (Meridian ERP) instead of the live TeamTailor API — useful for testing and development without a real API key', 'dotmavriq-job-sync'); ?>
                                        </label>
                                    </div>

                                    <!-- Preserve API token when saving advanced settings -->
                                    <input type="hidden" name="teamtailor_integrator_api_token" value="<?php echo esc_attr(get_option('teamtailor_integrator_api_token')); ?>" />

                                    <p>
                                        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save Advanced Settings', 'dotmavriq-job-sync'); ?>">
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
                        <h3><?php esc_html_e('Quick Help', 'dotmavriq-job-sync'); ?></h3>
                        <ul>
                            <li><strong><?php esc_html_e('API Token:', 'dotmavriq-job-sync'); ?></strong> <?php esc_html_e('Required to connect to TeamTailor', 'dotmavriq-job-sync'); ?></li>
                            <li><strong><?php esc_html_e('Mock Data:', 'dotmavriq-job-sync'); ?></strong> <?php esc_html_e('Enable in Advanced tab to test without a live API key', 'dotmavriq-job-sync'); ?></li>
                            <li><strong><?php esc_html_e('Sync:', 'dotmavriq-job-sync'); ?></strong> <?php esc_html_e('Import and update jobs from TeamTailor', 'dotmavriq-job-sync'); ?></li>
                            <li><strong><?php esc_html_e('Shortcode:', 'dotmavriq-job-sync'); ?></strong> <?php esc_html_e('Use', 'dotmavriq-job-sync'); ?> <code>[teamtailor_jobs]</code> <?php esc_html_e('to display jobs', 'dotmavriq-job-sync'); ?></li>
                        </ul>
                    </div>

                    <!-- API Info Box -->
                    <div class="teamtailor-help-box">
                        <h3><?php esc_html_e('About the API Test', 'dotmavriq-job-sync'); ?></h3>
                        <p><?php esc_html_e('The API Test helps you verify that your TeamTailor connection is working correctly. Additionally:', 'dotmavriq-job-sync'); ?></p>
                        <ul>
                            <li><?php esc_html_e('See the exact format of data from TeamTailor', 'dotmavriq-job-sync'); ?></li>
                            <li><?php esc_html_e('Verify job listings are available', 'dotmavriq-job-sync'); ?></li>
                            <li><?php esc_html_e('Understand the data structure before import', 'dotmavriq-job-sync'); ?></li>
                            <?php if ($use_mock): ?>
                            <li><em><?php esc_html_e('Currently showing mock data (Meridian ERP)', 'dotmavriq-job-sync'); ?></em></li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Documentation Box -->
                    <div class="teamtailor-card">
                        <div class="teamtailor-card-header">
                            <h3 class="teamtailor-card-title"><?php esc_html_e('Documentation', 'dotmavriq-job-sync'); ?></h3>
                        </div>
                        <div class="teamtailor-card-body">
                            <div class="teamtailor-buttons-group">
                                <p><?php esc_html_e('For TeamTailor API documentation, visit:', 'dotmavriq-job-sync'); ?></p>
                                <a href="https://docs.teamtailor.com/" target="_blank" class="button button-secondary">
                                    <?php esc_html_e('TeamTailor API Docs', 'dotmavriq-job-sync'); ?>
                                </a>

                                <p style="margin-top: 15px;"><?php esc_html_e('See our plugin documentation and keep up with updates:', 'dotmavriq-job-sync'); ?></p>
                                <a href="https://github.com/dotmavriq/TeamTailor-Integrator-For-WordPress" target="_blank" class="button button-secondary">
                                    <?php esc_html_e('GitHub Repository', 'dotmavriq-job-sync'); ?>
                                </a>

                                <p style="margin-top: 15px;"><?php esc_html_e('Request features or report bugs:', 'dotmavriq-job-sync'); ?></p>
                                <a href="https://github.com/dotmavriq/TeamTailor-Integrator-For-WordPress/issues" target="_blank" class="button button-secondary">
                                    <?php esc_html_e('GitHub Issues', 'dotmavriq-job-sync'); ?>
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
            // Verify nonce for security
            if (isset($_POST['teamtailor_token_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['teamtailor_token_nonce'])), 'teamtailor_save_token')) {
                $api_token = sanitize_text_field(wp_unslash($_POST['teamtailor_integrator_api_token']));

                // Validate the API token
                if (!empty($api_token)) {
                    // Save the token
                    update_option('teamtailor_integrator_api_token', $api_token);
                    echo '<div class="teamtailor-notice teamtailor-notice-success"><p>' . esc_html__('API Token saved successfully.', 'dotmavriq-job-sync') . '</p></div>';
                } else {
                    // Display error message
                    echo '<div class="teamtailor-notice teamtailor-notice-error"><p>' . esc_html__('API Token cannot be empty. Please check your token and try again.', 'dotmavriq-job-sync') . '</p></div>';
                }
            } else {
                echo '<div class="teamtailor-notice teamtailor-notice-error"><p>' . esc_html__('Security verification failed. Please try again.', 'dotmavriq-job-sync') . '</p></div>';
            }
        }

        // Get connection status
        $api_key = get_option('teamtailor_integrator_api_token');
        $has_key = !empty($api_key);
        $use_mock = (bool) get_option('teamtailor_integrator_use_mock_data', false);

        // Connection status indicator
        if ($use_mock) {
            echo '<div class="teamtailor-status-box teamtailor-status-success">';
            echo '<p><strong>' . esc_html__('Status:', 'dotmavriq-job-sync') . '</strong> ' . esc_html__('Mock data mode — using test data (Meridian ERP)', 'dotmavriq-job-sync') . '</p>';
            echo '</div>';
        } elseif ($has_key) {
            echo '<div class="teamtailor-status-box teamtailor-status-success">';
            echo '<p><strong>' . esc_html__('Status:', 'dotmavriq-job-sync') . '</strong> ' . esc_html__('Connected to TeamTailor API', 'dotmavriq-job-sync') . '</p>';
            echo '</div>';
        } else {
            echo '<div class="teamtailor-status-box teamtailor-status-warning">';
            echo '<p><strong>' . esc_html__('Status:', 'dotmavriq-job-sync') . '</strong> ' . esc_html__('Not connected — API token or mock data required', 'dotmavriq-job-sync') . '</p>';
            echo '</div>';
        }

        // Form HTML
        ?>
        <div class="teamtailor-token-form">
            <form method="post" action="">
                <?php wp_nonce_field('teamtailor_save_token', 'teamtailor_token_nonce'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php esc_html_e('API Token:', 'dotmavriq-job-sync'); ?></th>
                        <td>
                            <input
                                type="text"
                                name="teamtailor_integrator_api_token"
                                value="<?php echo esc_attr(get_option('teamtailor_integrator_api_token')); ?>"
                                placeholder="<?php esc_attr_e('Enter your TeamTailor API token', 'dotmavriq-job-sync'); ?>"
                            />
                            <p class="description">
                                <?php esc_html_e('Enter your TeamTailor API token here.', 'dotmavriq-job-sync'); ?>
                                <a href="https://docs.teamtailor.com/" target="_blank"><?php esc_html_e('Learn how to get your API token', 'dotmavriq-job-sync'); ?></a>
                            </p>
                        </td>
                    </tr>
                </table>
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save API Token', 'dotmavriq-job-sync'); ?>">
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
        $use_mock = (bool) get_option('teamtailor_integrator_use_mock_data', false);

        if (!$api_key && !$use_mock) {
            echo '<div class="teamtailor-notice teamtailor-notice-error">';
            echo '<p>' . esc_html__('API Key is not set. Please configure your API token in the Settings tab or enable "Use Mock Data" in the Advanced tab.', 'dotmavriq-job-sync') . '</p>';
            echo '</div>';
            return;
        }

        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-dotmavriq-job-sync-api.php';
        $api = new DotMavriQ_Job_Sync_API($api_key, $debug_mode);
        $response = $api->get_jobs();

        if ($response === false) {
            echo '<div class="teamtailor-notice teamtailor-notice-error">';
            echo '<p>' . esc_html__('Connection failed: Unable to connect to TeamTailor API. Please check your API token and try again.', 'dotmavriq-job-sync') . '</p>';
            echo '</div>';
        } else {
            // Get a count of jobs
            $job_count = count($response['data'] ?? []);

            echo '<div class="teamtailor-notice teamtailor-notice-success">';
            echo '<p>' . esc_html( sprintf(
                /* translators: %s: number of job listings received */
                _n( 'Connection successful! Received data for %s job listing.', 'Connection successful! Received data for %s job listings.', $job_count, 'dotmavriq-job-sync'),
                number_format_i18n( $job_count )
            ) ) . '</p>';
            echo '</div>';

            // Format the response as pretty JSON
            $prettyResponse = wp_json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            // Always show the JSON response
            ?>
            <div class="teamtailor-json-container">
                <div class="teamtailor-json-header">
                    <h3><?php esc_html_e('Raw JSON Response', 'dotmavriq-job-sync'); ?></h3>
                    <button id="teamtailor-copy-json" class="button" data-clipboard-target="#teamtailor-json-code">
                        <?php esc_html_e('Copy JSON', 'dotmavriq-job-sync'); ?>
                    </button>
                </div>

                <pre class="language-json"><code id="teamtailor-json-code" class="language-json"><?php echo esc_html($prettyResponse); ?></code></pre>
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
                'name' => __('TeamTailor Jobs', 'dotmavriq-job-sync'),
                'singular_name' => __('TeamTailor Job', 'dotmavriq-job-sync'),
                'add_new' => __('Add New', 'dotmavriq-job-sync'),
                'add_new_item' => __('Add New Job', 'dotmavriq-job-sync'),
                'edit_item' => __('Edit Job', 'dotmavriq-job-sync'),
                'new_item' => __('New Job', 'dotmavriq-job-sync'),
                'view_item' => __('View Job', 'dotmavriq-job-sync'),
                'search_items' => __('Search Jobs', 'dotmavriq-job-sync'),
                'not_found' => __('No jobs found', 'dotmavriq-job-sync'),
                'not_found_in_trash' => __('No jobs found in Trash', 'dotmavriq-job-sync'),
                'all_items' => __('All Jobs', 'dotmavriq-job-sync'),
                'archives' => __('Job Archives', 'dotmavriq-job-sync'),
                'insert_into_item' => __('Insert into job', 'dotmavriq-job-sync'),
                'uploaded_to_this_item' => __('Uploaded to this job', 'dotmavriq-job-sync'),
                'featured_image' => __('Job Image', 'dotmavriq-job-sync'),
                'set_featured_image' => __('Set job image', 'dotmavriq-job-sync'),
                'remove_featured_image' => __('Remove job image', 'dotmavriq-job-sync'),
                'use_featured_image' => __('Use as job image', 'dotmavriq-job-sync'),
                'filter_items_list' => __('Filter jobs list', 'dotmavriq-job-sync'),
                'items_list_navigation' => __('Jobs list navigation', 'dotmavriq-job-sync'),
                'items_list' => __('Jobs list', 'dotmavriq-job-sync'),
            ],
            'public' => true,
            'has_archive' => false,
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
        echo '<label for="teamtailor_job_id">' . esc_html__( 'Job ID:', 'dotmavriq-job-sync') . '</label>';
        echo '<input type="text" id="teamtailor_job_id" name="teamtailor_job_id" value="' . esc_attr($teamtailor_job_id) . '" size="25" />';

        echo '<label for="teamtailor_job_type">' . esc_html__( 'Job Type:', 'dotmavriq-job-sync') . '</label>';
        echo '<input type="text" id="teamtailor_job_type" name="teamtailor_job_type" value="' . esc_attr($teamtailor_job_type) . '" size="25" />';

        echo '<label for="teamtailor_company">' . esc_html__( 'Company:', 'dotmavriq-job-sync') . '</label>';
        echo '<input type="text" id="teamtailor_company" name="teamtailor_company" value="' . esc_attr(get_post_meta($post->ID, 'teamtailor_company', true)) . '" size="25" />';
    }

    /**
     * Save the metabox data
     *
     * @since    1.0.0
     * @param    int    $post_id    The ID of the post being saved.
     */
    public function save_job_metaboxes($post_id) {
        if (!isset($_POST['teamtailor_job_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['teamtailor_job_nonce'])), plugin_basename(__FILE__))) {
            return $post_id;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }
        $post_type = isset($_POST['post_type']) ? sanitize_key(wp_unslash($_POST['post_type'])) : '';
        if ('teamtailor_jobs' != $post_type || !current_user_can('edit_post', $post_id)) {
            return $post_id;
        }
        if (isset($_POST['teamtailor_job_id'])) {
            update_post_meta($post_id, '_teamtailor_job_id', sanitize_text_field(wp_unslash($_POST['teamtailor_job_id'])));
        }
        if (isset($_POST['teamtailor_job_type'])) {
            update_post_meta($post_id, '_teamtailor_job_type', sanitize_text_field(wp_unslash($_POST['teamtailor_job_type'])));
        }
        if (isset($_POST['teamtailor_company'])) {
            update_post_meta($post_id, 'teamtailor_company', sanitize_text_field(wp_unslash($_POST['teamtailor_company'])));
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
        $columns['job_id'] = __('Job ID', 'dotmavriq-job-sync');
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
        $columns['company'] = __('Company', 'dotmavriq-job-sync');
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
            $company = get_post_meta($post_id, 'teamtailor_company', true);
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
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-dotmavriq-job-sync-sync.php';
        $sync = new DotMavriQ_Job_Sync_Sync();
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
                $new_order['job_id'] = __('Job ID', 'dotmavriq-job-sync');
            } else if ($key != 'date') {
                $new_order[$key] = $value;
            }
        }
        $new_order['date'] = __('Date', 'dotmavriq-job-sync');
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
                'title' => __('TeamTailor Jobs Fields', 'dotmavriq-job-sync'),
                'fields' => array(
                    array(
                        'key' => 'field_teamtailor_job_id',
                        'label' => __('Job ID', 'dotmavriq-job-sync'),
                        'name' => '_teamtailor_job_id',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_teamtailor_departments',
                        'label' => __('Departments', 'dotmavriq-job-sync'),
                        'name' => 'teamtailor_departments',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_teamtailor_locations',
                        'label' => __('Locations', 'dotmavriq-job-sync'),
                        'name' => 'teamtailor_locations',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_teamtailor_roles',
                        'label' => __('Roles', 'dotmavriq-job-sync'),
                        'name' => 'teamtailor_roles',
                        'type' => 'text',
                    ),
                    array(
                        'key' => 'field_teamtailor_countries',
                        'label' => __('Countries', 'dotmavriq-job-sync'),
                        'name' => 'teamtailor_countries',
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
                    return $this->field_label;
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

        // Register each custom field (labels are already passed through __())
        $register_custom_field('_teamtailor_job_id', __('TeamTailor Job ID', 'dotmavriq-job-sync'));
        $register_custom_field('teamtailor_departments', __('TeamTailor Departments', 'dotmavriq-job-sync'));
        $register_custom_field('teamtailor_locations', __('TeamTailor Locations', 'dotmavriq-job-sync'));
        $register_custom_field('teamtailor_roles', __('TeamTailor Roles', 'dotmavriq-job-sync'));
    }
}

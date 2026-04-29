<?php
/**
 * Public-facing functionality
 *
 * @since      1.0.0
 *
 * @package    TeamTailor_Integrator
 * @subpackage TeamTailor_Integrator/public
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

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

        // Load Google Font (Inter) for a clean, modern look — only on pages with the shortcode.
        wp_enqueue_style(
            $this->plugin_name . '-fonts',
            'https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap',
            array(),
            null
        );
    }

    /**
     * Register the JavaScript for the public-facing side.
     *
     * @since    1.2.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name . '-public',
            plugin_dir_url(__FILE__) . '../assets/js/teamtailor-integrator-public.js',
            array(),
            $this->version,
            true
        );
    }

    /**
     * Get unique meta values for a given meta key.
     *
     * @since    1.0.0
     * @param    string    $meta_key    The meta key.
     * @return   array                  The unique meta values.
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

        return array_filter($meta_values, function($value) {
            return !empty($value);
        });
    }

    /**
     * Jobs shortcode callback.
     *
     * Shortcode attributes:
     *   title        — Heading text above the job board (default: '')
     *   show_filters — Whether to show the filter bar (default: 'true')
     *   navbar       — Whether to show the top navigation bar (default: 'true')
     *
     * @since    1.0.0
     * @param    array    $atts    The shortcode attributes.
     * @return   string            The shortcode output.
     */
    public function jobs_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title'        => '',
            'show_filters' => 'true',
            'navbar'       => 'true',
        ), $atts, 'teamtailor_jobs');

        $show_filters = filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN);
        $show_navbar  = filter_var($atts['navbar'], FILTER_VALIDATE_BOOLEAN);

        // Build the WP_Query to get published jobs.
        $args = array(
            'post_type'      => 'teamtailor_jobs',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        $jobs_query = new WP_Query($args);
        $jobs       = array();

        if ($jobs_query->have_posts()) {
            while ($jobs_query->have_posts()) {
                $jobs_query->the_post();
                $post_id   = get_the_ID();
                $permalink = get_permalink($post_id);

                $jobs[] = array(
                    'id'             => absint($post_id),
                    'title'          => get_the_title(),
                    'permalink'      => $permalink,
                    'department'     => get_post_meta($post_id, 'teamtailor_departments', true),
                    'location'       => get_post_meta($post_id, 'teamtailor_locations', true),
                    'country'        => get_post_meta($post_id, 'teamtailor_countries', true),
                    'role'           => get_post_meta($post_id, 'teamtailor_roles', true),
                    'company'        => get_post_meta($post_id, 'teamtailor_company', true),
                    'excerpt'        => get_the_excerpt(),
                );
            }
            wp_reset_postdata();
        }

        // Collect unique values for the JS-driven dropdowns.
        $departments = array_values(array_unique(array_filter(array_column($jobs, 'department'))));
        $locations   = array_values(array_unique(array_filter(array_column($jobs, 'location'))));
        $countries   = array_values(array_unique(array_filter(array_column($jobs, 'country'))));
        $roles       = array_values(array_unique(array_filter(array_column($jobs, 'role'))));

        sort($departments);
        sort($locations);
        sort($countries);
        sort($roles);

        ob_start();
        ?>

        <div class="tt-jobs" data-show-filters="<?php echo $show_filters ? '1' : '0'; ?>" data-show-navbar="<?php echo $show_navbar ? '1' : '0'; ?>">

            <?php if (!empty($atts['title'])): ?>
                <h2 class="tt-jobs__heading"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>

            <?php if ($show_navbar): ?>
                <div class="tt-jobs__navbar">
                    <div class="tt-jobs__navbar-brand">
                        <svg class="tt-jobs__navbar-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                        <span class="tt-jobs__navbar-title">Careers</span>
                    </div>
                    <div class="tt-jobs__navbar-count">
                        <span class="tt-jobs__count-number"><?php echo count($jobs); ?></span>
                        <span class="tt-jobs__count-label">open position<?php echo count($jobs) !== 1 ? 's' : ''; ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($show_filters): ?>
                <div class="tt-jobs__filters">
                    <div class="tt-jobs__filter-group">
                        <label class="tt-jobs__filter-label" for="tt-filter-search">Search</label>
                        <input
                            type="text"
                            id="tt-filter-search"
                            class="tt-jobs__filter-input tt-jobs__filter-input--search"
                            placeholder="Job title, keyword…"
                            autocomplete="off"
                        >
                    </div>

                    <div class="tt-jobs__filter-group">
                        <label class="tt-jobs__filter-label" for="tt-filter-department">Department</label>
                        <select id="tt-filter-department" class="tt-jobs__filter-input tt-jobs__filter-select">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dep): ?>
                                <option value="<?php echo esc_attr($dep); ?>"><?php echo esc_html($dep); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="tt-jobs__filter-group">
                        <label class="tt-jobs__filter-label" for="tt-filter-location">Location</label>
                        <select id="tt-filter-location" class="tt-jobs__filter-input tt-jobs__filter-select">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $loc): ?>
                                <option value="<?php echo esc_attr($loc); ?>"><?php echo esc_html($loc); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="tt-jobs__filter-group">
                        <label class="tt-jobs__filter-label" for="tt-filter-role">Role</label>
                        <select id="tt-filter-role" class="tt-jobs__filter-input tt-jobs__filter-select">
                            <option value="">All Roles</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo esc_attr($role); ?>"><?php echo esc_html($role); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="button" class="tt-jobs__filter-clear" id="tt-filter-clear" style="display:none;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                        Clear
                    </button>
                </div>
            <?php endif; ?>

            <div class="tt-jobs__results" id="tt-jobs-results">
                <div class="tt-jobs__grid" id="tt-jobs-grid">
                    <?php foreach ($jobs as $job): ?>
                        <div class="tt-jobs__card" data-title="<?php echo esc_attr(strtolower($job['title'])); ?>" data-department="<?php echo esc_attr(strtolower($job['department'])); ?>" data-location="<?php echo esc_attr(strtolower($job['location'])); ?>" data-role="<?php echo esc_attr(strtolower($job['role'])); ?>">
                            <div class="tt-jobs__card-body">
                                <h3 class="tt-jobs__card-title">
                                    <a href="<?php echo esc_url($job['permalink']); ?>"><?php echo esc_html($job['title']); ?></a>
                                </h3>
                                <?php if (!empty($job['excerpt'])): ?>
                                    <p class="tt-jobs__card-excerpt"><?php echo esc_html($job['excerpt']); ?></p>
                                <?php endif; ?>
                                <div class="tt-jobs__card-meta">
                                    <?php if (!empty($job['department'])): ?>
                                        <span class="tt-jobs__badge tt-jobs__badge--department"><?php echo esc_html($job['department']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($job['location'])): ?>
                                        <span class="tt-jobs__badge tt-jobs__badge--location">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                                <circle cx="12" cy="10" r="3"></circle>
                                            </svg>
                                            <?php echo esc_html($job['location']); ?>
                                            <?php if (!empty($job['country'])): ?>
                                                <span class="tt-jobs__badge-country">, <?php echo esc_html($job['country']); ?></span>
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($job['role'])): ?>
                                        <span class="tt-jobs__badge tt-jobs__badge--role"><?php echo esc_html($job['role']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="tt-jobs__card-footer">
                                <a href="<?php echo esc_url($job['permalink']); ?>" class="tt-jobs__card-link">
                                    View Job
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                        <polyline points="12 5 19 12 12 19"></polyline>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="tt-jobs__empty" id="tt-jobs-empty" style="display:none;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        <line x1="8" y1="11" x2="14" y2="11"></line>
                    </svg>
                    <h3>No matching positions</h3>
                    <p>Try adjusting your filters or search term.</p>
                </div>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }
}

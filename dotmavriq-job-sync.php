<?php
/**
 * Plugin Name: dotMavriQ's Job Sync for Teamtailor
 * Plugin URI:  https://github.com/dotMavriQ/TeamTailor-Integrator-For-WordPress
 * Description: Sync job listings from Teamtailor to your WordPress site. Display jobs via shortcodes and manage them from your dashboard. Independent integration; not affiliated with Teamtailor AB.
 * Version:     1.2.5
 * Requires at least: 5.8
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * Author:      Jonatan Jansson
 * Author URI:  https://github.com/dotMavriQ
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dotmavriq-job-sync
 * Domain Path: /languages
 *
 * @package DotMavriQ_Job_Sync
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('DOTMAVRIQ_JOB_SYNC_VERSION', '1.2.5');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-dotmavriq-job-sync.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function dotmavriq_job_sync_run() {
    $plugin = new DotMavriQ_Job_Sync();
    $plugin->run();
}
dotmavriq_job_sync_run();
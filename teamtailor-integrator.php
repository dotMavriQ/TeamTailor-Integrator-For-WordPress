<?php
/**
 * Plugin Name: TeamTailor Integrator
 * Plugin URI:  https://github.com/dotMavriQ/TeamTailor-Integrator-For-WordPress
 * Description: Seamlessly integrate TeamTailor recruitment services with your WordPress site. Sync job listings, display them via shortcodes, and manage your recruitment process directly through your website.
 * Version:     1.2.0
 * Requires at least: 5.8
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Author:      Jonatan Jansson
 * Author URI:  https://github.com/dotMavriQ
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: teamtailor-integrator
 * Domain Path: /languages
 *
 * @package TeamTailor_Integrator
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
define('TEAMTAILOR_INTEGRATOR_VERSION', '1.2.0');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-teamtailor-integrator.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_teamtailor_integrator() {
    $plugin = new TeamTailor_Integrator();
    $plugin->run();
}
run_teamtailor_integrator();
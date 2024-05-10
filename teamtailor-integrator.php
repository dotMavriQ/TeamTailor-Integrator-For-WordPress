<?php
/**
 * Plugin Name: TeamTailor Integrator
 * Description: Integration with TeamTailor for WordPress.
 * Version: 1.1.1
 * Author: Jonatan Jansson
 * URI: https://github.com/dotMavriQ/TeamTailor-Integrator-For-WordPress
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
define('TEAMTAILOR_INTEGRATOR_VERSION', '1.1.1');

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
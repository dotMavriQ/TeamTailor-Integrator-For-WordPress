<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @since      1.1.1
 * @package    DotMavriQ_Job_Sync
 */

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options.
delete_option('teamtailor_integrator_api_token');
delete_option('teamtailor_integrator_debug_mode');
delete_option('teamtailor_integrator_use_mock_data');
delete_option('teamtailor_last_sync_time');

// Delete all job posts created by the plugin.
$dotmavriq_job_sync_jobs = get_posts(
    array(
        'post_type'      => 'teamtailor_jobs',
        'posts_per_page' => -1,
        'post_status'    => 'any',
    )
);

foreach ($dotmavriq_job_sync_jobs as $dotmavriq_job_sync_job) {
    // Delete all post meta associated with the job.
    delete_post_meta($dotmavriq_job_sync_job->ID, '_teamtailor_job_id');
    delete_post_meta($dotmavriq_job_sync_job->ID, '_teamtailor_job_type');
    delete_post_meta($dotmavriq_job_sync_job->ID, 'teamtailor_departments');
    delete_post_meta($dotmavriq_job_sync_job->ID, 'teamtailor_locations');
    delete_post_meta($dotmavriq_job_sync_job->ID, 'teamtailor_countries');
    delete_post_meta($dotmavriq_job_sync_job->ID, 'teamtailor_roles');
    delete_post_meta($dotmavriq_job_sync_job->ID, 'teamtailor_company');

    // Delete the post itself.
    wp_delete_post($dotmavriq_job_sync_job->ID, true);
}

// Also clean up legacy meta keys from previous versions.
global $wpdb;
$wpdb->delete(
    $wpdb->postmeta,
    array('meta_key' => 'departments')
);
$wpdb->delete(
    $wpdb->postmeta,
    array('meta_key' => 'locations')
);
$wpdb->delete(
    $wpdb->postmeta,
    array('meta_key' => 'countries')
);
$wpdb->delete(
    $wpdb->postmeta,
    array('meta_key' => 'roles')
);
$wpdb->delete(
    $wpdb->postmeta,
    array('meta_key' => 'company')
);

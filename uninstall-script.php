<?php
/**
 * Uninstall script for Sports Registration System
 *
 * This script is executed when the plugin is uninstalled.
 * It removes all data created by the plugin, including:
 * - Database tables
 * - Custom post types
 * - Options
 * - Scheduled crons
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Get global database instance
global $wpdb;

// Define table names
$registrations_table = $wpdb->prefix . 'srs_registrations';

// Check if we should completely remove data
$remove_data = get_option('srs_remove_data_on_uninstall', false);

if ($remove_data) {
    // Drop custom tables
    $wpdb->query("DROP TABLE IF EXISTS {$registrations_table}");
    
    // Delete all family accounts
    $family_posts = get_posts(array(
        'post_type' => 'srs_family',
        'numberposts' => -1,
        'post_status' => 'any',
        'fields' => 'ids',
    ));
    
    if (!empty($family_posts)) {
        foreach ($family_posts as $post_id) {
            wp_delete_post($post_id, true);
        }
    }
    
    // Delete all child profiles
    $child_posts = get_posts(array(
        'post_type' => 'srs_child',
        'numberposts' => -1,
        'post_status' => 'any',
        'fields' => 'ids',
    ));
    
    if (!empty($child_posts)) {
        foreach ($child_posts as $post_id) {
            wp_delete_post($post_id, true);
        }
    }
    
    // Delete all registration seasons
    $season_posts = get_posts(array(
        'post_type' => 'srs_season',
        'numberposts' => -1,
        'post_status' => 'any',
        'fields' => 'ids',
    ));
    
    if (!empty($season_posts)) {
        foreach ($season_posts as $post_id) {
            wp_delete_post($post_id, true);
        }
    }
    
    // Delete options
    $options = array(
        'srs_global_settings',
        'srs_basketball_settings',
        'srs_soccer_settings',
        'srs_cheerleading_settings',
        'srs_volleyball_settings',
        'srs_email_settings',
        'srs_remove_data_on_uninstall',
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
    
    // Clear scheduled crons
    wp_clear_scheduled_hook('srs_send_registration_reminder');
    wp_clear_scheduled_hook('srs_send_season_reminders');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

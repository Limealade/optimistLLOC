<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Include database migration for uninstall method
require_once plugin_dir_path(__FILE__) . 'includes/class-srs-database-migration.php';

// Call uninstall method
SRS_Database_Migration::uninstall();

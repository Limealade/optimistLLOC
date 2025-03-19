<?php
/**
 * Database Migration class
 * Handles database structure creation and updates
 */
class SRS_Database_Migration {
    private $current_version;
    private $new_version = '1.1.0';

    public function __construct() {
        $this->current_version = get_option('srs_db_version', '1.0.0');
        add_action('plugins_loaded', array($this, 'check_database_update'));
    }

    /**
     * Check if database needs updating and perform migrations if needed
     */
    public function check_database_update() {
        if (version_compare($this->current_version, $this->new_version, '<')) {
            $this->perform_migrations();
        }
    }

    /**
     * Run database migrations based on version
     */
    private function perform_migrations() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        // Registrations table with improved indexes
        $registrations_table = $wpdb->prefix . 'srs_registrations';
        $sql_registrations = "CREATE TABLE $registrations_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            form_type varchar(20) NOT NULL,
            first_name varchar(50) NOT NULL,
            last_name varchar(50) NOT NULL,
            form_data longtext NOT NULL,
            payment_status varchar(20) DEFAULT 'none',
            payment_id varchar(100) DEFAULT '',
            payment_amount decimal(10,2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_form_type (form_type),
            KEY idx_payment_status (payment_status),
            KEY idx_name_search (last_name, first_name),
            KEY idx_created_at (created_at)
        ) $charset_collate;";

        dbDelta($sql_registrations);

        // Add version-specific migrations
        if (version_compare($this->current_version, '1.0.5', '<')) {
            // Run 1.0.5 specific migrations
            $this->migrate_to_version_1_0_5();
        }
        
        if (version_compare($this->current_version, '1.1.0', '<')) {
            // Run 1.1.0 specific migrations
            $this->migrate_to_version_1_1_0();
        }

        // Update version
        update_option('srs_db_version', $this->new_version);
    }
    
    /**
     * Migrations for version 1.0.5
     */
    private function migrate_to_version_1_0_5() {
        global $wpdb;
        
        // Add token_created column to track session token age for security
        $wpdb->query("
            ALTER TABLE {$wpdb->prefix}srs_registrations 
            ADD COLUMN payment_gateway VARCHAR(50) DEFAULT '' 
            AFTER payment_status
        ");
        
        // Check if the post meta for token_created exists, if not add it
        $families = get_posts(array(
            'post_type' => 'srs_family',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ));
        
        if (!empty($families)) {
            foreach ($families as $family_id) {
                $token = get_post_meta($family_id, 'session_token', true);
                if (!empty($token) && !get_post_meta($family_id, 'token_created', true)) {
                    update_post_meta($family_id, 'token_created', time());
                }
            }
        }
    }
    
    /**
     * Migrations for version 1.1.0
     */
    private function migrate_to_version_1_1_0() {
        global $wpdb;
        
        // Add a family_id column to the registrations table
        $wpdb->query("
            ALTER TABLE {$wpdb->prefix}srs_registrations 
            ADD COLUMN family_id BIGINT(20) DEFAULT NULL AFTER last_name,
            ADD KEY idx_family_id (family_id)
        ");
        
        // Migrate existing registrations to link with family accounts where possible
        $registrations = $wpdb->get_results("
            SELECT id, form_data FROM {$wpdb->prefix}srs_registrations 
            WHERE family_id IS NULL
        ");
        
        foreach ($registrations as $registration) {
            $form_data = json_decode($registration->form_data, true);
            if (isset($form_data['family_id']) && !empty($form_data['family_id'])) {
                $family_id = intval($form_data['family_id']);
                $wpdb->update(
                    $wpdb->prefix . 'srs_registrations',
                    array('family_id' => $family_id),
                    array('id' => $registration->id),
                    array('%d'),
                    array('%d')
                );
            }
        }
    }

    /**
     * Remove all plugin data on uninstall
     */
    public static function uninstall() {
        global $wpdb;
        
        // Only proceed if remove data option is enabled
        if (!get_option('srs_remove_data_on_uninstall', false)) {
            return;
        }

        // Remove database tables
        $tables = array(
            $wpdb->prefix . 'srs_registrations'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        // Delete all family accounts, child profiles, and seasons
        $post_types = array('srs_family', 'srs_child', 'srs_season');
        foreach ($post_types as $post_type) {
            $items = get_posts(array(
                'post_type' => $post_type,
                'numberposts' => -1,
                'post_status' => 'any',
                'fields' => 'ids',
            ));
            
            if (!empty($items)) {
                foreach ($items as $item_id) {
                    wp_delete_post($item_id, true);
                }
            }
        }

        // Remove plugin options
        $options = array(
            'srs_db_version',
            'srs_global_settings',
            'srs_basketball_settings',
            'srs_soccer_settings',
            'srs_cheerleading_settings',
            'srs_volleyball_settings',
            'srs_email_settings',
            'srs_remove_data_on_uninstall'
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
}

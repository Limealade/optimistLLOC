<?php
class SRS_Database_Migration {
    private $current_version;
    private $new_version = '1.1.0';

    public function __construct() {
        $this->current_version = get_option('srs_db_version', '1.0.0');
        add_action('plugins_loaded', [$this, 'check_database_update']);
    }

    public function check_database_update() {
        if (version_compare($this->current_version, $this->new_version, '<')) {
            $this->perform_migrations();
        }
    }

    private function perform_migrations() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        // Registrations table
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
            PRIMARY KEY (id),
            KEY idx_form_type (form_type),
            KEY idx_payment_status (payment_status),
            KEY idx_name_search (last_name, first_name)
        ) $charset_collate;";

        dbDelta($sql_registrations);

        // Update version
        update_option('srs_db_version', $this->new_version);
    }

    public static function uninstall() {
        global $wpdb;

        // Optional: Remove all plugin-related data
        $tables = [
            $wpdb->prefix . 'srs_registrations'
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        // Remove plugin options
        delete_option('srs_db_version');
        delete_option('srs_global_settings');
    }
}

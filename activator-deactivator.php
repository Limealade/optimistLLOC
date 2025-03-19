<?php
/**
 * Fired during plugin activation
 */
class SRS_Activator {
    /**
     * Create necessary database tables
     */
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Create registrations table
        $table_name = $wpdb->prefix . 'srs_registrations';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            form_type varchar(20) NOT NULL,
            first_name varchar(50) NOT NULL,
            last_name varchar(50) NOT NULL,
            form_data longtext NOT NULL,
            payment_status varchar(20) DEFAULT 'none',
            payment_id varchar(100) DEFAULT '',
            payment_amount decimal(10,2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY form_type (form_type),
            KEY payment_status (payment_status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Initialize default options if they don't exist
        if (!get_option('srs_global_settings')) {
            update_option('srs_global_settings', array(
                'square_enabled' => 0,
                'square_app_id' => '',
                'square_location_id' => '',
                'square_access_token' => '',
                'paypal_enabled' => 0,
                'paypal_client_id' => '',
                'paypal_secret' => '',
                'google_sheets_enabled' => 0,
                'google_sheets_id' => '',
                'google_service_account_json' => '',
                'disclosure_text' => 'I hereby certify that the information provided is true and accurate.',
            ));
        }

        // Initialize default settings for each sport
        $sports = array('basketball', 'soccer', 'cheerleading', 'volleyball');
        foreach ($sports as $sport) {
            if (!get_option('srs_' . $sport . '_settings')) {
                update_option('srs_' . $sport . '_settings', array(
                    'enabled' => 1,
                    'title' => ucfirst($sport) . ' Registration',
                    'price' => '0',
                    'required_fields' => array(
                        'first_name', 'last_name', 'gender', 'shirt_size', 'address',
                        'city', 'state', 'zip', 'phone', 'dob', 'school', 
                        'emergency_contact', 'emergency_phone'
                    ),
                ));
            }
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

/**
 * Fired during plugin deactivation
 */
class SRS_Deactivator {
    /**
     * Clean up on deactivation
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Nothing else to do on deactivation
        // We don't want to remove tables or settings in case the plugin is reactivated
    }
}

/**
 * Internationalization handler
 */
class SRS_i18n {
    /**
     * Load the plugin text domain for translation
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'sports-registration',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}

/**
 * Plugin loader class
 */
class SRS_Loader {
    protected $actions;
    protected $filters;

    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    public function run() {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
    }
}

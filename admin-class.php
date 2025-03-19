<?php
/**
 * The admin-specific functionality of the plugin.
 */
class SRS_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, SRS_PLUGIN_URL . 'admin/css/srs-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, SRS_PLUGIN_URL . 'admin/js/srs-admin.js', array('jquery'), $this->version, false);
    }

    /**
     * Add menu items for the plugin.
     */
    public function add_plugin_admin_menu() {
        // Main menu
        add_menu_page(
            'Sports Registration System',
            'Sports Registration',
            'manage_options',
            'sports-registration',
            array($this, 'display_plugin_dashboard'),
            'dashicons-clipboard',
            30
        );

        // Dashboard submenu
        add_submenu_page(
            'sports-registration',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'sports-registration',
            array($this, 'display_plugin_dashboard')
        );

        // Global Settings submenu
        add_submenu_page(
            'sports-registration',
            'Global Settings',
            'Global Settings',
            'manage_options',
            'sports-registration-global',
            array($this, 'display_global_settings')
        );

        // Individual sport settings
        add_submenu_page(
            'sports-registration',
            'Basketball Settings',
            'Basketball',
            'manage_options',
            'sports-registration-basketball',
            array($this, 'display_basketball_settings')
        );

        add_submenu_page(
            'sports-registration',
            'Soccer Settings',
            'Soccer',
            'manage_options',
            'sports-registration-soccer',
            array($this, 'display_soccer_settings')
        );

        add_submenu_page(
            'sports-registration',
            'Cheerleading Settings',
            'Cheerleading',
            'manage_options',
            'sports-registration-cheerleading',
            array($this, 'display_cheerleading_settings')
        );

        add_submenu_page(
            'sports-registration',
            'Volleyball Settings',
            'Volleyball',
            'manage_options',
            'sports-registration-volleyball',
            array($this, 'display_volleyball_settings')
        );

        // Registrations list
        add_submenu_page(
            'sports-registration',
            'Registrations',
            'Registrations',
            'manage_options',
            'sports-registration-list',
            array($this, 'display_registrations_list')
        );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        // Global settings
        register_setting(
            'srs_global_settings',
            'srs_global_settings',
            array($this, 'validate_global_settings')
        );

        // Sport-specific settings
        register_setting(
            'srs_basketball_settings',
            'srs_basketball_settings',
            array($this, 'validate_sport_settings')
        );

        register_setting(
            'srs_soccer_settings',
            'srs_soccer_settings',
            array($this, 'validate_sport_settings')
        );

        register_setting(
            'srs_cheerleading_settings',
            'srs_cheerleading_settings',
            array($this, 'validate_sport_settings')
        );

        register_setting(
            'srs_volleyball_settings',
            'srs_volleyball_settings',
            array($this, 'validate_sport_settings')
        );
    }

    /**
     * Validate global settings.
     */
    public function validate_global_settings($input) {
        $output = array();

        // Payment gateways
        $output['square_enabled'] = isset($input['square_enabled']) ? 1 : 0;
        $output['square_app_id'] = sanitize_text_field($input['square_app_id'] ?? '');
        $output['square_location_id'] = sanitize_text_field($input['square_location_id'] ?? '');
        $output['square_access_token'] = sanitize_text_field($input['square_access_token'] ?? '');

        $output['paypal_enabled'] = isset($input['paypal_enabled']) ? 1 : 0;
        $output['paypal_client_id'] = sanitize_text_field($input['paypal_client_id'] ?? '');
        $output['paypal_secret'] = sanitize_text_field($input['paypal_secret'] ?? '');

        // Google Sheets integration
        $output['google_sheets_enabled'] = isset($input['google_sheets_enabled']) ? 1 : 0;
        $output['google_sheets_id'] = sanitize_text_field($input['google_sheets_id'] ?? '');
        $output['google_service_account_json'] = sanitize_textarea_field($input['google_service_account_json'] ?? '');

        // Disclosure text
        $output['disclosure_text'] = wp_kses_post($input['disclosure_text'] ?? 'I hereby certify that the information provided is true and accurate.');

        return $output;
    }

    /**
     * Validate sport settings.
     */
    public function validate_sport_settings($input) {
        $output = array();

        $output['enabled'] = isset($input['enabled']) ? 1 : 0;
        $output['title'] = sanitize_text_field($input['title'] ?? 'Registration Form');
        $output['price'] = floatval($input['price'] ?? 0);
        $output['required_fields'] = isset($input['required_fields']) && is_array($input['required_fields']) ? $input['required_fields'] : array();

        return $output;
    }

    /**
     * Display plugin dashboard.
     */
    public function display_plugin_dashboard() {
        // Get registration counts
        global $wpdb;
        $table_name = $wpdb->prefix . 'srs_registrations';

        $counts = array(
            'basketball' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE form_type = %s", 'basketball')),
            'soccer' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE form_type = %s", 'soccer')),
            'cheerleading' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE form_type = %s", 'cheerleading')),
            'volleyball' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE form_type = %s", 'volleyball')),
        );

        $total_registrations = array_sum($counts);

        // Get payment stats
        $paid_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE payment_status = 'paid'");
        $payment_total = $wpdb->get_var("SELECT SUM(payment_amount) FROM $table_name WHERE payment_status = 'paid'");

        // Recent registrations
        $recent_registrations = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 5"
        );

        include SRS_PLUGIN_DIR . 'admin/partials/srs-admin-dashboard.php';
    }

    /**
     * Display global settings page.
     */
    public function display_global_settings() {
        $settings = get_option('srs_global_settings', array(
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

        include SRS_PLUGIN_DIR . 'admin/partials/srs-admin-global-settings.php';
    }

    /**
     * Display basketball settings page.
     */
    public function display_basketball_settings() {
        $this->display_sport_settings('basketball');
    }

    /**
     * Display soccer settings page.
     */
    public function display_soccer_settings() {
        $this->display_sport_settings('soccer');
    }

    /**
     * Display cheerleading settings page.
     */
    public function display_cheerleading_settings() {
        $this->display_sport_settings('cheerleading');
    }

    /**
     * Display volleyball settings page.
     */
    public function display_volleyball_settings() {
        $this->display_sport_settings('volleyball');
    }

    /**
     * Display sport settings page.
     */
    private function display_sport_settings($sport) {
        $settings = get_option('srs_' . $sport . '_settings', array(
            'enabled' => 1,
            'title' => ucfirst($sport) . ' Registration',
            'price' => '0',
            'required_fields' => array(
                'first_name', 'last_name', 'gender', 'shirt_size', 'address',
                'city', 'state', 'zip', 'phone', 'dob', 'school', 
                'emergency_contact', 'emergency_phone'
            ),
        ));

        // Get all available fields
        $form = new SRS_Forms();
        $fields = $form->get_all_available_fields();

        include SRS_PLUGIN_DIR . 'admin/partials/srs-admin-sport-settings.php';
    }

    /**
     * Display registrations list page.
     */
    public function display_registrations_list() {
        // Initialize registrations list table
        if (!class_exists('WP_List_Table')) {
            require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
        }

        require_once SRS_PLUGIN_DIR . 'admin/class-srs-registrations-list-table.php';
        $registrations_table = new SRS_Registrations_List_Table();
        $registrations_table->prepare_items();

        include SRS_PLUGIN_DIR . 'admin/partials/srs-admin-registrations-list.php';
    }

    /**
     * Get all available fields for a form.
     */
    public function get_all_available_fields() {
        return array(
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'gender' => 'Gender',
            'shirt_size' => 'Shirt Size',
            'address' => 'Physical Address',
            'city' => 'City',
            'state' => 'State',
            'zip' => 'Zip Code',
            'phone' => 'Preferred Phone Number',
            'dob' => 'Date of Birth',
            'school' => 'School',
            'medical_issues' => 'Medical Issues',
            'medical_insurance' => 'Medical Insurance',
            'siblings' => 'Siblings (Name and Age)',
            'emergency_contact' => 'Emergency Contact Name',
            'emergency_phone' => 'Emergency Contact Phone',
            'social_media_waiver' => 'Social Media Waiver',
        );
    }
}

/**
 * Admin dashboard view template
 */
function srs_admin_dashboard_template() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <div class="srs-dashboard-wrapper">
            <div class="srs-dashboard-stats">
                <div class="srs-stat-box">
                    <h3>Total Registrations</h3>
                    <div class="srs-stat-number"><?php echo esc_html($total_registrations); ?></div>
                </div>
                
                <div class="srs-stat-box">
                    <h3>Basketball</h3>
                    <div class="srs-stat-number"><?php echo esc_html($counts['basketball']); ?></div>
                </div>
                
                <div class="srs-stat-box">
                    <h3>Soccer</h3>
                    <div class="srs-stat-number"><?php echo esc_html($counts['soccer']); ?></div>
                </div>
                
                <div class="srs-stat-box">
                    <h3>Cheerleading</h3>
                    <div class="srs-stat-number"><?php echo esc_html($counts['cheerleading']); ?></div>
                </div>
                
                <div class="srs-stat-box">
                    <h3>Volleyball</h3>
                    <div class="srs-stat-number"><?php echo esc_html($counts['volleyball']); ?></div>
                </div>
                
                <div class="srs-stat-box">
                    <h3>Payments</h3>
                    <div class="srs-stat-number">$<?php echo esc_html(number_format($payment_total, 2)); ?></div>
                    <div class="srs-stat-subtitle"><?php echo esc_html($paid_count); ?> paid registrations</div>
                </div>
            </div>
            
            <div class="srs-recent-registrations">
                <h2>Recent Registrations</h2>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Sport</th>
                            <th>Date</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_registrations)): ?>
                            <tr>
                                <td colspan="4">No registrations found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_registrations as $registration): ?>
                                <?php
                                $form_data = json_decode($registration->form_data, true);
                                $name = sprintf('%s %s', 
                                    sanitize_text_field($form_data['first_name'] ?? ''),
                                    sanitize_text_field($form_data['last_name'] ?? '')
                                );
                                ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=sports-registration-list&action=view&id=' . $registration->id)); ?>">
                                            <?php echo esc_html($name); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html(ucfirst($registration->form_type)); ?></td>
                                    <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($registration->created_at))); ?></td>
                                    <td>
                                        <?php
                                        if ($registration->payment_status === 'paid') {
                                            echo '<span class="srs-status-paid">Paid</span>';
                                        } elseif ($registration->payment_status === 'pending') {
                                            echo '<span class="srs-status-pending">Pending</span>';
                                        } else {
                                            echo '<span class="srs-status-none">None</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <p class="srs-view-all">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=sports-registration-list')); ?>" class="button">View All Registrations</a>
                </p>
            </div>
            
            <div class="srs-quick-links">
                <h2>Quick Links</h2>
                
                <div class="srs-links-grid">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=sports-registration-global')); ?>" class="srs-quick-link-card">
                        <div class="srs-card-icon dashicons dashicons-admin-settings"></div>
                        <h3>Global Settings</h3>
                        <p>Configure payment methods, Google Sheets integration, and more.</p>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=sports-registration-basketball')); ?>" class="srs-quick-link-card">
                        <div class="srs-card-icon dashicons dashicons-clipboard"></div>
                        <h3>Basketball Settings</h3>
                        <p>Configure the basketball registration form.</p>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=sports-registration-soccer')); ?>" class="srs-quick-link-card">
                        <div class="srs-card-icon dashicons dashicons-clipboard"></div>
                        <h3>Soccer Settings</h3>
                        <p>Configure the soccer registration form.</p>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=sports-registration-cheerleading')); ?>" class="srs-quick-link-card">
                        <div class="srs-card-icon dashicons dashicons-clipboard"></div>
                        <h3>Cheerleading Settings</h3>
                        <p>Configure the cheerleading registration form.</p>
                    </a>
                    
                    <a href="<?php echo esc_url(admin_url('admin.php?page=sports-registration-volleyball')); ?>" class="srs-quick-link-card">
                        <div class="srs-card-icon dashicons dashicons-clipboard"></div>
                        <h3>Volleyball Settings</h3>
                        <p>Configure the volleyball registration form.</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php
}

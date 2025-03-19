/**
 * This artifact contains fixes for structural issues in the Sports Registration System
 */

/**
 * FIX 1: Properly integrating form-builder-update.php into the SRS_Forms class
 * Problem: Standalone method without class wrapper
 */

// Original problematic file structure - form-builder-update.php containing just:
public function render_form($form_type) {
    // Method implementation...
}

// Fix: Properly integrate into the SRS_Forms class in form-builder-class.php:
class SRS_Forms {
    private $form_fields = array();
    private $required_fields = array();
    private $form_settings = array();
    
    public function __construct() {
        $this->init_form_settings();
        add_shortcode('srs_registration_form', array($this, 'shortcode_output'));
    }
    
    // Other existing methods...
    
    /**
     * Render form for a specific sport
     */
    public function render_form($form_type) {
        if (!in_array($form_type, array('basketball', 'soccer', 'cheerleading', 'volleyball'))) {
            $form_type = 'basketball'; // Default
        }
        
        $settings = $this->form_settings[$form_type];
        if (empty($settings['enabled'])) {
            return '<p>Registration for this sport is currently disabled.</p>';
        }
        
        $fields = $this->get_form_fields($form_type);
        
        // Get global pricing settings
        $global_settings = get_option('srs_global_settings', array());
        $base_pricing = isset($global_settings['base_pricing']) ? $global_settings['base_pricing'] : array();
        
        // Get the base price for this sport type
        $base_price = isset($base_pricing[$form_type]) ? floatval($base_pricing[$form_type]) : floatval($settings['price']);
        
        // Form rendering code...
        
        return $rendered_form;
    }
}

/**
 * FIX 2: Resolving file dependency issues in sports-registration-plugin.php
 * Problem: References to files that might not exist
 */

// Original vulnerable code:
require_once SRS_PLUGIN_DIR . 'includes/class-srs-activator.php';
require_once SRS_PLUGIN_DIR . 'includes/class-srs-deactivator.php';
require_once SRS_PLUGIN_DIR . 'includes/class-srs-loader.php';
require_once SRS_PLUGIN_DIR . 'includes/class-srs-i18n.php';
require_once SRS_PLUGIN_DIR . 'admin/class-srs-admin.php';
require_once SRS_PLUGIN_DIR . 'public/class-srs-public.php';
require_once SRS_PLUGIN_DIR . 'includes/class-srs-forms.php';
require_once SRS_PLUGIN_DIR . 'includes/class-srs-payments.php';
require_once SRS_PLUGIN_DIR . 'includes/class-srs-google-sheet.php';

// Fixed code with dependency checking:
// Define required files
$required_files = array(
    'includes/class-srs-activator.php',
    'includes/class-srs-deactivator.php',
    'includes/class-srs-loader.php',
    'includes/class-srs-i18n.php',
    'admin/class-srs-admin.php',
    'public/class-srs-public.php',
    'includes/class-srs-forms.php',
    'includes/class-srs-payments.php',
    'includes/class-srs-google-sheet.php',
);

// Check and include required files
$missing_files = array();
foreach ($required_files as $file) {
    $file_path = SRS_PLUGIN_DIR . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        $missing_files[] = $file;
    }
}

// Handle missing files
if (!empty($missing_files)) {
    add_action('admin_notices', function() use ($missing_files) {
        ?>
        <div class="notice notice-error">
            <p>
                <strong>Sports Registration System:</strong> 
                The following required files are missing:
                <ul>
                    <?php foreach ($missing_files as $file): ?>
                        <li><?php echo esc_html($file); ?></li>
                    <?php endforeach; ?>
                </ul>
                The plugin may not function correctly. Please reinstall the plugin.
            </p>
        </div>
        <?php
    });
}

/**
 * FIX 3: Addressing duplicate CSS files issue
 * Problem: admin-css.css and admin-css (1).css contain identical code
 * 
 * Solution: Keep only one file and update references in the code
 */

// Instead of:
// wp_enqueue_style($this->plugin_name, SRS_PLUGIN_URL . 'admin/css/srs-admin.css', array(), $this->version, 'all');

// Use this consistent approach:
/**
 * Register the stylesheets for the admin area.
 */
public function enqueue_styles() {
    // Main admin stylesheet
    wp_enqueue_style(
        $this->plugin_name . '-admin', 
        SRS_PLUGIN_URL . 'admin/css/srs-admin.css', 
        array(), 
        $this->version, 
        'all'
    );
    
    // Family accounts stylesheet
    wp_enqueue_style(
        $this->plugin_name . '-family', 
        SRS_PLUGIN_URL . 'admin/css/srs-family.css', 
        array($this->plugin_name . '-admin'), 
        $this->version, 
        'all'
    );
    
    // Pricing stylesheet
    wp_enqueue_style(
        $this->plugin_name . '-pricing', 
        SRS_PLUGIN_URL . 'admin/css/srs-pricing.css', 
        array($this->plugin_name . '-admin'), 
        $this->version, 
        'all'
    );
}

/**
 * FIX 4: Proper Database Table Creation in activator-deactivator.php
 * Problem: Database tables created without checking if they exist
 */

// Original vulnerable code:
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
    
    // Initialize default options...
}

// Fixed code:
public static function activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    
    // Check if database schema needs updating
    $db_version = get_option('srs_db_version', '0.0');
    $current_version = '1.0.0'; // Update this when schema changes
    
    // Only create/update tables if needed
    if (version_compare($db_version, $current_version, '<')) {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
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
            KEY payment_status (payment_status),
            KEY name_search (last_name, first_name),
            KEY created_date (created_at)
        ) $charset_collate;";
        
        dbDelta($sql);
        
        // Update database version
        update_option('srs_db_version', $current_version);
    }
    
    // Initialize default options...
}

/**
 * FIX 5: Implementing proper email template escaping in email-notifications.php
 * Problem: Email templates aren't properly escaped
 */

// Original vulnerable code:
private function send_email($to, $template, $replacement_data) {
    // Replace placeholders in subject
    $subject = $template['subject'];
    
    foreach ($replacement_data as $key => $value) {
        $subject = str_replace('{' . $key . '}', $value, $subject);
    }
    
    // Replace placeholders in body
    $body = $template['body'];
    
    foreach ($replacement_data as $key => $value) {
        $body = str_replace('{' . $key . '}', $value, $body);
    }
    
    // Set headers
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $this->from_name . ' <' . $this->from_email . '>',
    );
    
    // Send email
    wp_mail($to, $subject, $body, $headers);
}

// Fixed code:
private function send_email($to, $template, $replacement_data) {
    // Sanitize email recipient
    $to = sanitize_email($to);
    if (!is_email($to)) {
        error_log("Sports Registration System: Invalid email recipient: $to");
        return false;
    }
    
    // Sanitize replacement data
    foreach ($replacement_data as $key => $value) {
        if (is_string($value)) {
            $replacement_data[$key] = wp_kses_post($value);
        }
    }
    
    // Replace placeholders in subject
    $subject = $template['subject'];
    
    foreach ($replacement_data as $key => $value) {
        $subject = str_replace('{' . $key . '}', $value, $subject);
    }
    
    // Ensure subject is properly sanitized
    $subject = sanitize_text_field($subject);
    
    // Replace placeholders in body
    $body = $template['body'];
    
    foreach ($replacement_data as $key => $value) {
        $body = str_replace('{' . $key . '}', $value, $body);
    }
    
    // Set headers
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . sanitize_text_field($this->from_name) . ' <' . sanitize_email($this->from_email) . '>',
    );
    
    // Send email
    return wp_mail($to, $subject, $body, $headers);
}

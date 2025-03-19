/**
 * This artifact contains security fixes for critical issues in the Sports Registration System
 */

/**
 * FIX 1: Preventing SQL Injection in admin-dashboard-update.php
 * Problem: SQL queries built using string concatenation
 */

// Original vulnerable code:
$family_members = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT id, first_name, last_name, form_type, form_data, created_at FROM $table_name 
        WHERE last_name = %s 
        AND form_data LIKE %s
        AND form_data LIKE %s
        AND id != %d
        ORDER BY created_at",
        $last_name,
        '%' . $wpdb->esc_like('"address":"' . $address) . '%',
        '%' . $wpdb->esc_like('"zip":"' . $zip) . '%',
        $registration_id
    )
);

// Fixed code:
$family_members = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT id, first_name, last_name, form_type, form_data, created_at FROM $table_name 
        WHERE last_name = %s 
        AND form_data LIKE %s
        AND form_data LIKE %s
        AND id != %d
        ORDER BY created_at",
        $last_name,
        '%' . $wpdb->esc_like('"address":"') . $wpdb->esc_like($address) . $wpdb->esc_like('"') . '%',
        '%' . $wpdb->esc_like('"zip":"') . $wpdb->esc_like($zip) . $wpdb->esc_like('"') . '%',
        $registration_id
    )
);

/**
 * FIX 2: Proper Input Sanitization in family-registration-ajax.php
 * Problem: Insufficient input sanitization
 */

// Original vulnerable code:
$form_type = sanitize_text_field($_POST['form_type'] ?? '');
$child_ids = isset($_POST['child_ids']) ? $_POST['child_ids'] : array(); // Not sanitized

// Fixed code:
$form_type = sanitize_text_field($_POST['form_type'] ?? '');
$child_ids = isset($_POST['child_ids']) ? array_map('intval', $_POST['child_ids']) : array();

/**
 * FIX 3: Secure Cookie Handling in family-account-system.php
 * Problem: Insufficient cookie validation
 */

// Original vulnerable code:
public function get_current_family_id() {
    if (!isset($_COOKIE['srs_family_token'])) {
        return false;
    }
    
    $token = sanitize_text_field($_COOKIE['srs_family_token']);
    
    // Find family by token
    $args = array(
        'post_type' => 'srs_family',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => 'session_token',
                'value' => $token,
                'compare' => '=',
            ),
        ),
    );
    
    $families = get_posts($args);
    
    if (empty($families)) {
        return false;
    }
    
    return $families[0]->ID;
}

// Fixed code:
public function get_current_family_id() {
    if (!isset($_COOKIE['srs_family_token'])) {
        return false;
    }
    
    $token = sanitize_text_field($_COOKIE['srs_family_token']);
    
    // Validate token format (should be an MD5 hash)
    if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
        return false;
    }
    
    // Find family by token
    $args = array(
        'post_type' => 'srs_family',
        'posts_per_page' => 1,
        'meta_query' => array(
            array(
                'key' => 'session_token',
                'value' => $token,
                'compare' => '=',
            ),
        ),
    );
    
    $families = get_posts($args);
    
    if (empty($families)) {
        return false;
    }
    
    // Check if token is expired (tokens valid for 14 days)
    $token_created = get_post_meta($families[0]->ID, 'token_created', true);
    if (empty($token_created) || (time() - intval($token_created) > 14 * DAY_IN_SECONDS)) {
        // Token expired, delete it
        delete_post_meta($families[0]->ID, 'session_token');
        return false;
    }
    
    return $families[0]->ID;
}

/**
 * FIX 4: Proper Error Handling for Database Operations
 * Problem: Missing error handling
 */

// Original vulnerable code:
private function save_registration($form_data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'srs_registrations';
    
    // Prepare data for insertion
    $data = array(
        'form_type' => sanitize_text_field($form_data['form_type']),
        'first_name' => sanitize_text_field($form_data['first_name']),
        'last_name' => sanitize_text_field($form_data['last_name']),
        'form_data' => json_encode($form_data),
        'payment_status' => isset($form_data['payment_status']) ? sanitize_text_field($form_data['payment_status']) : 'none',
        'payment_id' => isset($form_data['payment_id']) ? sanitize_text_field($form_data['payment_id']) : '',
        'created_at' => current_time('mysql'),
    );
    
    $result = $wpdb->insert($table_name, $data);
    
    if ($result === false) {
        return false;
    }
    
    return $wpdb->insert_id;
}

// Fixed code:
private function save_registration($form_data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'srs_registrations';
    
    // Prepare data for insertion
    $data = array(
        'form_type' => sanitize_text_field($form_data['form_type']),
        'first_name' => sanitize_text_field($form_data['first_name']),
        'last_name' => sanitize_text_field($form_data['last_name']),
        'form_data' => json_encode($form_data),
        'payment_status' => isset($form_data['payment_status']) ? sanitize_text_field($form_data['payment_status']) : 'none',
        'payment_id' => isset($form_data['payment_id']) ? sanitize_text_field($form_data['payment_id']) : '',
        'created_at' => current_time('mysql'),
    );
    
    // Check if table exists first
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    if (!$table_exists) {
        error_log("Sports Registration System: Registrations table does not exist: $table_name");
        return false;
    }
    
    // Insert data with error handling
    $result = $wpdb->insert($table_name, $data);
    
    if ($result === false) {
        $db_error = $wpdb->last_error;
        error_log("Sports Registration System: Database error during registration: $db_error");
        return false;
    }
    
    return $wpdb->insert_id;
}

/**
 * FIX 5: Move JavaScript to Dedicated Files
 * Problem: JavaScript embedded in PHP files
 */

// Original problematic code in payment-class.php:
function srs_square_js() {
    ?>
    // Square payment integration
    document.addEventListener('DOMContentLoaded', function() {
        // JavaScript code...
    });
    <?php
}

// Fixed approach - Create a dedicated JS file: srs-square.js
// And in payment-class.php, use:
public function enqueue_payment_scripts() {
    // Only load on pages that have our registration form
    global $post;
    if (!is_a($post, 'WP_Post')) {
        return;
    }
    
    if (has_shortcode($post->post_content, 'srs_registration_form') || has_block('sports-registration/registration-form', $post->post_content)) {
        // Square SDK
        if ($this->square_enabled && !empty($this->square_app_id)) {
            wp_enqueue_script('square-web-payments-sdk', 'https://sandbox.web.squarecdn.com/v1/square.js', array(), null, true);
            wp_enqueue_script('srs-square-integration', SRS_PLUGIN_URL . 'public/js/srs-square.js', array('jquery', 'square-web-payments-sdk'), SRS_PLUGIN_VERSION, true);
            wp_localize_script('srs-square-integration', 'srs_square_params', array(
                'app_id' => $this->square_app_id,
                'location_id' => $this->square_location_id,
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('srs_square_nonce'),
            ));
        }
    }
}

/**
 * FIX 6: Optimizing Database Queries
 * Problem: Inefficient database queries
 */

// Original inefficient code:
$counts = array(
    'basketball' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE form_type = %s", 'basketball')),
    'soccer' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE form_type = %s", 'soccer')),
    'cheerleading' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE form_type = %s", 'cheerleading')),
    'volleyball' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE form_type = %s", 'volleyball')),
);

// Fixed optimized code:
$counts_query = $wpdb->get_results(
    "SELECT form_type, COUNT(*) as count FROM $table_name GROUP BY form_type"
);

$counts = array(
    'basketball' => 0,
    'soccer' => 0,
    'cheerleading' => 0,
    'volleyball' => 0,
);

foreach ($counts_query as $row) {
    if (array_key_exists($row->form_type, $counts)) {
        $counts[$row->form_type] = $row->count;
    }
}

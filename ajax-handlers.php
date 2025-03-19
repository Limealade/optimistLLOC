<?php
/**
 * AJAX handlers for family discount calculations and registrations
 * - Fixed SQL injection vulnerabilities
 * - Added proper sanitization
 * - Improved error handling
 */

/**
 * Check for family discount AJAX handler
 */
function srs_check_family_discount_ajax() {
    // Verify nonce
    check_ajax_referer('srs_family_discount_nonce', 'security');
    
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $address = sanitize_text_field($_POST['address'] ?? '');
    $zip = sanitize_text_field($_POST['zip'] ?? '');
    $form_type = sanitize_text_field($_POST['form_type'] ?? '');
    
    if (empty($last_name) || empty($address) || empty($zip) || empty($form_type)) {
        wp_send_json_error(array(
            'message' => 'Missing required information.',
        ));
        return;
    }
    
    // Get global settings
    $global_settings = get_option('srs_global_settings', array());
    $base_pricing = isset($global_settings['base_pricing']) ? $global_settings['base_pricing'] : array();
    $family_discount = isset($global_settings['family_discount']) ? $global_settings['family_discount'] : array();
    
    // Get base price for this sport
    $base_price = isset($base_pricing[$form_type]) ? floatval($base_pricing[$form_type]) : 0;
    
    // Check if family discounts are enabled
    if (empty($family_discount['enabled'])) {
        wp_send_json_success(array(
            'base_price' => $base_price,
            'discounted_price' => $base_price,
            'discount_percent' => 0,
            'family_count' => 0,
        ));
        return;
    }
    
    // Count existing family registrations
    $family_count = srs_get_family_registration_count($last_name, $address, $zip);
    
    // Calculate discount percentage
    $discount_percent = 0;
    
    if ($family_count > 0) {
        if ($family_count === 1) {
            // Second child
            $discount_percent = floatval($family_discount['second_child']);
        } elseif ($family_count === 2) {
            // Third child
            $discount_percent = floatval($family_discount['third_child']);
        } else {
            // Fourth or more child
            $discount_percent = floatval($family_discount['additional_child']);
        }
    }
    
    // Calculate discounted price
    $discount_amount = $base_price * ($discount_percent / 100);
    $discounted_price = $base_price - $discount_amount;
    
    wp_send_json_success(array(
        'base_price' => $base_price,
        'discounted_price' => round($discounted_price, 2),
        'discount_percent' => $discount_percent,
        'family_count' => $family_count,
    ));
}
add_action('wp_ajax_srs_check_family_discount', 'srs_check_family_discount_ajax');
add_action('wp_ajax_nopriv_srs_check_family_discount', 'srs_check_family_discount_ajax');

/**
 * Get the number of children already registered from the same family
 * Fixed to prevent SQL injection
 * 
 * @param string $last_name Family last name
 * @param string $address Family address
 * @param string $zip Family zip code
 * @return int Number of existing registrations from the same family
 */
function srs_get_family_registration_count($last_name, $address, $zip) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'srs_registrations';
    
    // Validate table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    if (!$table_exists) {
        error_log("Sports Registration System: Registrations table does not exist: $table_name");
        return 0;
    }
    
    // Safely prepare query with properly escaped LIKE clauses
    $query = $wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name 
        WHERE last_name = %s 
        AND form_data LIKE %s
        AND form_data LIKE %s
        AND created_at >= %s",
        $last_name,
        '%' . $wpdb->esc_like('"address":"') . $wpdb->esc_like($address) . $wpdb->esc_like('"') . '%',
        '%' . $wpdb->esc_like('"zip":"') . $wpdb->esc_like($zip) . $wpdb->esc_like('"') . '%',
        date('Y-01-01') // Current year only
    );
    
    $count = $wpdb->get_var($query);
    
    if ($count === null) {
        error_log("Sports Registration System: Database error in family count query: " . $wpdb->last_error);
        return 0;
    }
    
    return intval($count);
}

/**
 * Modify the registration form submission process to apply family discounts
 */
function srs_apply_family_discount($fee, $form_data) {
    // Get the form type and base price
    $form_type = sanitize_text_field($form_data['form_type'] ?? '');
    $base_price = floatval($form_data['base_price'] ?? 0);
    
    if (empty($form_type) || $base_price <= 0) {
        return $base_price;
    }
    
    // Get family information
    $last_name = sanitize_text_field($form_data['last_name'] ?? '');
    $address = sanitize_text_field($form_data['address'] ?? '');
    $zip = sanitize_text_field($form_data['zip'] ?? '');
    
    if (empty($last_name) || empty($address) || empty($zip)) {
        return $base_price;
    }
    
    // Get global settings
    $global_settings = get_option('srs_global_settings', array());
    $family_discount = isset($global_settings['family_discount']) ? $global_settings['family_discount'] : array();
    
    // Check if family discounts are enabled
    if (empty($family_discount['enabled'])) {
        return $base_price;
    }
    
    // Check for a family account
    $family_id = isset($form_data['family_id']) ? intval($form_data['family_id']) : 0;
    
    if ($family_id > 0) {
        // If using family accounts, get exact child count
        $count = srs_get_family_child_registration_count($family_id, $form_type);
    } else {
        // Otherwise, use address matching
        $count = srs_get_family_registration_count($last_name, $address, $zip);
    }
    
    // Calculate discount percentage
    $discount_percent = 0;
    
    if ($count > 0) {
        if ($count === 1) {
            // Second child
            $discount_percent = floatval($family_discount['second_child']);
        } elseif ($count === 2) {
            // Third child
            $discount_percent = floatval($family_discount['third_child']);
        } else {
            // Fourth or more child
            $discount_percent = floatval($family_discount['additional_child']);
        }
    }
    
    // Calculate discounted price
    $discount_amount = $base_price * ($discount_percent / 100);
    $discounted_price = $base_price - $discount_amount;
    
    return round($discounted_price, 2);
}
add_filter('srs_calculate_registration_fee', 'srs_apply_family_discount', 10, 2);

/**
 * Get count of child registrations for a specific family account
 * 
 * @param int $family_id The family account ID
 * @param string $form_type The sport type
 * @return int Number of existing registrations
 */
function srs_get_family_child_registration_count($family_id, $form_type) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'srs_registrations';
    
    // Validate inputs
    $family_id = intval($family_id);
    $form_type = sanitize_text_field($form_type);
    
    if ($family_id <= 0 || empty($form_type)) {
        return 0;
    }
    
    // Safely prepare query
    $query = $wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name 
        WHERE family_id = %d
        AND form_type = %s
        AND created_at >= %s",
        $family_id,
        $form_type,
        date('Y-01-01') // Current year only
    );
    
    $count = $wpdb->get_var($query);
    
    if ($count === null) {
        error_log("Sports Registration System: Database error in family child count query: " . $wpdb->last_error);
        return 0;
    }
    
    return intval($count);
}

/**
 * Update the process_form_submission method to use the discounted price
 */
function srs_update_payment_amount($form_data) {
    // Check if there's a base price
    if (isset($form_data['base_price']) && floatval($form_data['base_price']) > 0) {
        // Calculate the discounted price
        $discounted_price = apply_filters('srs_calculate_registration_fee', $form_data['base_price'], $form_data);
        
        // Update the payment amount
        $form_data['payment_amount'] = $discounted_price;
        
        // Add discount info to form data for reference
        $form_data['original_price'] = floatval($form_data['base_price']);
        $form_data['discount_amount'] = floatval($form_data['base_price']) - $discounted_price;
    }
    
    return $form_data;
}
add_filter('srs_before_process_registration', 'srs_update_payment_amount', 10, 1);

/**
 * Family dashboard AJAX handler - Safely update family profile
 */
function srs_update_family_profile_ajax() {
    // Verify nonce
    check_ajax_referer('srs_family_accounts_nonce', 'nonce');
    
    // Get family ID from session with validation
    $family_id = srs_get_current_family_id();
    
    if (!$family_id) {
        wp_send_json_error(array(
            'message' => 'Authentication failed. Please log in again.',
        ));
        return;
    }
    
    // Sanitize all fields
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $address = sanitize_text_field($_POST['address'] ?? '');
    $city = sanitize_text_field($_POST['city'] ?? '');
    $state = sanitize_text_field($_POST['state'] ?? '');
    $zip = sanitize_text_field($_POST['zip'] ?? '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        wp_send_json_error(array(
            'message' => 'Please fill in all required fields.',
        ));
        return;
    }
    
    // Validate email format
    if (!is_email($email)) {
        wp_send_json_error(array(
            'message' => 'Please enter a valid email address.',
        ));
        return;
    }
    
    // Check if email already exists (except for current family)
    $args = array(
        'post_type' => 'srs_family',
        'posts_per_page' => 1,
        'post__not_in' => array($family_id),
        'meta_query' => array(
            array(
                'key' => 'email',
                'value' => $email,
                'compare' => '=',
            ),
        ),
    );
    
    $existing_families = get_posts($args);
    
    if (!empty($existing_families)) {
        wp_send_json_error(array(
            'message' => 'An account with this email address already exists.',
        ));
        return;
    }
    
    // Update family title
    wp_update_post(array(
        'ID' => $family_id,
        'post_title' => $first_name . ' ' . $last_name . ' Family',
    ));
    
    // Update family data
    update_post_meta($family_id, 'first_name', $first_name);
    update_post_meta($family_id, 'last_name', $last_name);
    update_post_meta($family_id, 'email', $email);
    update_post_meta($family_id, 'phone', $phone);
    update_post_meta($family_id, 'address', $address);
    update_post_meta($family_id, 'city', $city);
    update_post_meta($family_id, 'state', $state);
    update_post_meta($family_id, 'zip', $zip);
    
    // Update password if provided
    if (!empty($password)) {
        // Use WordPress password hashing
        update_post_meta($family_id, 'password', wp_hash_password($password));
        
        // Update session token
        $session_token = md5(time() . $email . wp_generate_password(32, false));
        update_post_meta($family_id, 'session_token', $session_token);
        update_post_meta($family_id, 'token_created', time());
        
        // Update cookie
        setcookie('srs_family_token', $session_token, time() + (14 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
    }
    
    // Get updated family data
    $family_data = srs_get_family_data($family_id);
    
    wp_send_json_success(array(
        'message' => 'Profile updated successfully.',
        'family' => $family_data,
    ));
}
add_action('wp_ajax_srs_update_family_profile', 'srs_update_family_profile_ajax');

/**
 * Get current family ID from session with improved security
 */
function srs_get_current_family_id() {
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

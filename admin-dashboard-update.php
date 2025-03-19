<?php
/**
 * Add family metrics to the admin dashboard
 */

/**
 * Get family registration metrics
 */
function srs_get_family_metrics() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'srs_registrations';
    
    // Current year only
    $current_year = date('Y-01-01');
    
    // Get all registrations for current year - using proper prepared statement
    $registrations = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, last_name, form_data, created_at FROM %i WHERE created_at >= %s",
            $table_name,
            $current_year
        )
    );
    
    // Process registrations to identify families
    $families = array();
    
    foreach ($registrations as $registration) {
        $form_data = json_decode($registration->form_data, true);
        $last_name = $registration->last_name;
        $address = isset($form_data['address']) ? sanitize_text_field($form_data['address']) : '';
        $zip = isset($form_data['zip']) ? sanitize_text_field($form_data['zip']) : '';
        
        if (empty($last_name) || empty($address) || empty($zip)) {
            continue;
        }
        
        // Create a unique family key using last name, address, and zip
        $family_key = md5($last_name . '|' . $address . '|' . $zip);
        
        if (!isset($families[$family_key])) {
            $families[$family_key] = array(
                'last_name' => $last_name,
                'address' => $address,
                'zip' => $zip,
                'children' => array(),
            );
        }
        
        $families[$family_key]['children'][] = array(
            'id' => $registration->id,
            'first_name' => isset($form_data['first_name']) ? sanitize_text_field($form_data['first_name']) : '',
            'form_type' => isset($form_data['form_type']) ? sanitize_text_field($form_data['form_type']) : '',
            'created_at' => $registration->created_at,
        );
    }
    
    // Calculate metrics
    $total_families = count($families);
    $families_with_multiple_children = 0;
    $children_with_discounts = 0;
    $total_children = 0;
    
    foreach ($families as $family) {
        $child_count = count($family['children']);
        $total_children += $child_count;
        
        if ($child_count > 1) {
            $families_with_multiple_children++;
            $children_with_discounts += ($child_count - 1); // All children except the first get discounts
        }
    }
    
    // Average children per family
    $avg_children_per_family = $total_families > 0 ? round($total_children / $total_families, 1) : 0;
    
    return array(
        'total_families' => $total_families,
        'families_with_multiple_children' => $families_with_multiple_children,
        'children_with_discounts' => $children_with_discounts,
        'total_children' => $total_children,
        'avg_children_per_family' => $avg_children_per_family,
        'families' => $families,
    );
}

/**
 * Display family metrics in the admin dashboard
 */
function srs_display_family_metrics() {
    $metrics = srs_get_family_metrics();
    ?>
    <div class="srs-dashboard-section">
        <h2>Family Registrations</h2>
        
        <div class="srs-family-metrics">
            <div class="srs-family-metric-box">
                <div class="srs-family-metric-number"><?php echo esc_html($metrics['total_families']); ?></div>
                <div class="srs-family-metric-label">Total Families</div>
            </div>
            
            <div class="srs-family-metric-box">
                <div class="srs-family-metric-number"><?php echo esc_html($metrics['total_children']); ?></div>
                <div class="srs-family-metric-label">Total Children</div>
            </div>
            
            <div class="srs-family-metric-box">
                <div class="srs-family-metric-number"><?php echo esc_html($metrics['avg_children_per_family']); ?></div>
                <div class="srs-family-metric-label">Avg. Children per Family</div>
            </div>
            
            <div class="srs-family-metric-box">
                <div class="srs-family-metric-number"><?php echo esc_html($metrics['families_with_multiple_children']); ?></div>
                <div class="srs-family-metric-label">Families with Multiple Children</div>
            </div>
            
            <div class="srs-family-metric-box">
                <div class="srs-family-metric-number"><?php echo esc_html($metrics['children_with_discounts']); ?></div>
                <div class="srs-family-metric-label">Children with Family Discounts</div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Add family information to registration details page
 */
function srs_display_family_members($registration_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'srs_registrations';
    
    // Get current registration
    $registration = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM %i WHERE id = %d",
            $table_name,
            $registration_id
        )
    );
    
    if (!$registration) {
        return;
    }
    
    $form_data = json_decode($registration->form_data, true);
    $last_name = $registration->last_name;
    $address = isset($form_data['address']) ? sanitize_text_field($form_data['address']) : '';
    $zip = isset($form_data['zip']) ? sanitize_text_field($form_data['zip']) : '';
    
    if (empty($last_name) || empty($address) || empty($zip)) {
        return;
    }
    
    // Find other family members - fixed SQL injection risk
    $family_members = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, first_name, last_name, form_type, form_data, created_at FROM %i 
            WHERE last_name = %s 
            AND form_data LIKE %s
            AND form_data LIKE %s
            AND id != %d
            ORDER BY created_at",
            $table_name,
            $last_name,
            '%' . $wpdb->esc_like('"address":"' . $address) . '%',
            '%' . $wpdb->esc_like('"zip":"' . $zip) . '%',
            $registration_id
        )
    );
    
    if (empty($family_members)) {
        return;
    }
    
    // Get global settings for discounts
    $global_settings = get_option('srs_global_settings', array());
    $family_discount = isset($global_settings['family_discount']) ? $global_settings['family_discount'] : array();
    $discount_enabled = !empty($family_discount['enabled']);
    ?>
    <div class="srs-family-registrations">
        <h3>Other Family Members</h3>
        
        <ul class="srs-family-members">
            <?php foreach ($family_members as $index => $member): ?>
                <?php
                $member_data = json_decode($member->form_data, true);
                $discount_percent = 0;
                
                if ($discount_enabled) {
                    if ($index === 0) {
                        // First child - no discount
                        $discount_percent = 0;
                    } elseif ($index === 1) {
                        // Second child
                        $discount_percent = floatval($family_discount['second_child']);
                    } elseif ($index === 2) {
                        // Third child
                        $discount_percent = floatval($family_discount['third_child']);
                    } else {
                        // Fourth or more child
                        $discount_percent = floatval($family_discount['additional_child']);
                    }
                }
                ?>
                <li>
                    <span class="srs-family-member-name">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=sports-registration-list&action=view&registration=' . $member->id)); ?>">
                            <?php echo esc_html($member->first_name . ' ' . $member->last_name); ?>
                        </a>
                        <?php if ($discount_percent > 0): ?>
                            <span class="srs-family-discount-indicator">
                                <?php echo esc_html($discount_percent . '% discount'); ?>
                            </span>
                        <?php endif; ?>
                    </span>
                    <div class="srs-family-member-info">
                        <?php echo esc_html(ucfirst($member->form_type)); ?> | 
                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($member->created_at))); ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}

/**
 * Add family members to registration details page
 */
function srs_add_family_members_to_registration_details() {
    add_action('srs_after_registration_details', 'srs_display_family_members', 10, 1);
}
add_action('init', 'srs_add_family_members_to_registration_details');

/**
 * Add family metrics to dashboard
 */
function srs_add_family_metrics_to_dashboard() {
    add_action('srs_dashboard_after_stats', 'srs_display_family_metrics');
}
add_action('init', 'srs_add_family_metrics_to_dashboard');

<?php
/**
 * AJAX handlers for family registration
 */

/**
 * Get child data AJAX handler
 */
function srs_get_child_data_ajax() {
    // Verify nonce
    check_ajax_referer('srs_family_accounts_nonce', 'nonce');
    
    // Get family ID from session
    $family_id = srs_get_current_family_id();
    
    if (!$family_id) {
        wp_send_json_error(array(
            'message' => __('You must be logged in to access child data.', 'sports-registration'),
        ));
        return;
    }
    
    $child_id = intval($_POST['child_id'] ?? 0);
    
    // Verify child belongs to family
    $children = get_post_meta($family_id, 'children', true);
    
    if (empty($children) || !is_array($children) || !in_array($child_id, $children)) {
        wp_send_json_error(array(
            'message' => __('You do not have permission to access this child profile.', 'sports-registration'),
        ));
        return;
    }
    
    // Get child data
    $child_data = srs_get_child_data($child_id);
    
    if (!$child_data) {
        wp_send_json_error(array(
            'message' => __('Child profile not found.', 'sports-registration'),
        ));
        return;
    }
    
    wp_send_json_success(array(
        'child' => $child_data,
    ));
}
add_action('wp_ajax_srs_get_child_data', 'srs_get_child_data_ajax');

/**
 * Update family AJAX handler
 */
function srs_update_family_ajax() {
    // Verify nonce
    check_ajax_referer('srs_family_accounts_nonce', 'nonce');
    
    // Get family ID from session
    $family_id = srs_get_current_family_id();
    
    if (!$family_id) {
        wp_send_json_error(array(
            'message' => __('You must be logged in to update your profile.', 'sports-registration'),
        ));
        return;
    }
    
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $address = sanitize_text_field($_POST['address'] ?? '');
    $city = sanitize_text_field($_POST['city'] ?? '');
    $state = sanitize_text_field($_POST['state'] ?? '');
    $zip = sanitize_text_field($_POST['zip'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($address) || empty($city) || empty($state) || empty($zip)) {
        wp_send_json_error(array(
            'message' => __('Please fill in all required fields.', 'sports-registration'),
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
            'message' => __('An account with this email address already exists.', 'sports-registration'),
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
        update_post_meta($family_id, 'password', wp_hash_password($password));
    }
    
    // Get updated family data
    $family_data = srs_get_family_data($family_id);
    
    wp_send_json_success(array(
        'message' => __('Profile updated successfully.', 'sports-registration'),
        'family' => $family_data,
    ));
}
add_action('wp_ajax_srs_update_family', 'srs_update_family_ajax');

/**
 * Calculate registration fees AJAX handler
 */
function srs_calculate_registration_fees_ajax() {
    // Verify nonce
    check_ajax_referer('srs_family_accounts_nonce', 'nonce');
    
    // Get family ID from session
    $family_id = srs_get_current_family_id();
    
    if (!$family_id) {
        wp_send_json_error(array(
            'message' => __('You must be logged in to calculate registration fees.', 'sports-registration'),
        ));
        return;
    }
    
    $form_type = sanitize_text_field($_POST['form_type'] ?? '');
    $child_ids = json_decode(stripslashes($_POST['child_ids'] ?? '[]'), true);
    
    if (empty($form_type) || empty($child_ids)) {
        wp_send_json_error(array(
            'message' => __('Invalid request.', 'sports-registration'),
        ));
        return;
    }
    
    // Verify children belong to family
    $family_children = get_post_meta($family_id, 'children', true);
    
    if (empty($family_children) || !is_array($family_children)) {
        wp_send_json_error(array(
            'message' => __('You do not have any children in your family account.', 'sports-registration'),
        ));
        return;
    }
    
    foreach ($child_ids as $child_id) {
        if (!in_array($child_id, $family_children)) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to register one or more of these children.', 'sports-registration'),
            ));
            return;
        }
    }
    
    // Get base price for the sport
    $global_settings = get_option('srs_global_settings', array());
    $base_pricing = isset($global_settings['base_pricing']) ? $global_settings['base_pricing'] : array();
    $base_price = isset($base_pricing[$form_type]) ? floatval($base_pricing[$form_type]) : 0;
    
    // Calculate fees for each child
    $fees = array(
        'children' => array(),
        'total' => 0,
    );
    
    foreach ($child_ids as $index => $child_id) {
        $child = get_post($child_id);
        
        if (!$child || $child->post_type !== 'srs_child') {
            continue;
        }
        
        $child_first_name = get_post_meta($child_id, 'first_name', true);
        $child_last_name = get_post_meta($child_id, 'last_name', true);
        
        // Calculate fee with family discount
        $child_fee = $base_price;
        
        if ($index > 0) {
            // Apply family discount
            $family_discount = isset($global_settings['family_discount']) ? $global_settings['family_discount'] : array();
            
            if (!empty($family_discount['enabled'])) {
                $discount_percent = 0;
                
                if ($index === 1) {
                    // Second child
                    $discount_percent = floatval($family_discount['second_child']);
                } elseif ($index === 2) {
                    // Third child
                    $discount_percent = floatval($family_discount['third_child']);
                } else {
                    // Fourth or more child
                    $discount_percent = floatval($family_discount['additional_child']);
                }
                
                $discount_amount = $child_fee * ($discount_percent / 100);
                $child_fee -= $discount_amount;
            }
        }
        
        $fees['children'][] = array(
            'id' => $child_id,
            'name' => $child_first_name . ' ' . $child_last_name,
            'fee' => $child_fee,
        );
        
        $fees['total'] += $child_fee;
    }
    
    wp_send_json_success(array(
        'fees' => $fees,
    ));
}
add_action('wp_ajax_srs_calculate_registration_fees', 'srs_calculate_registration_fees_ajax');

/**
 * Submit family registration AJAX handler
 */
function srs_submit_family_registration_ajax() {
    // Verify nonce
    check_ajax_referer('srs_family_accounts_nonce', 'nonce');
    
    // Get family ID from session
    $family_id = srs_get_current_family_id();
    
    if (!$family_id) {
        wp_send_json_error(array(
            'message' => __('You must be logged in to submit registrations.', 'sports-registration'),
        ));
        return;
    }
    
    $form_type = sanitize_text_field($_POST['form_type'] ?? '');
    $child_ids = isset($_POST['child_ids']) ? array_map('intval', $_POST['child_ids']) : array();
    $social_media_waiver = sanitize_text_field($_POST['social_media_waiver'] ?? 'no');
    $disclosure = isset($_POST['disclosure']) ? 1 : 0;
    $signature = sanitize_text_field($_POST['signature'] ?? '');
    
    // Validate required fields
    if (empty($form_type) || empty($child_ids) || empty($disclosure) || empty($signature)) {
        wp_send_json_error(array(
            'message' => __('Please fill in all required fields and provide a signature.', 'sports-registration'),
        ));
        return;
    }
    
    // Verify children belong to family
    $family_children = get_post_meta($family_id, 'children', true);
    
    if (empty($family_children) || !is_array($family_children)) {
        wp_send_json_error(array(
            'message' => __('You do not have any children in your family account.', 'sports-registration'),
        ));
        return;
    }
    
    foreach ($child_ids as $child_id) {
        if (!in_array($child_id, $family_children)) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to register one or more of these children.', 'sports-registration'),
            ));
            return;
        }
    }
    
    // Get family data
    $family_data = srs_get_family_data($family_id);
    
    // Register each child
    $registration_ids = array();
    
    foreach ($child_ids as $child_id) {
        $child_data = srs_get_child_data($child_id);
        
        if (!$child_data) {
            continue;
        }
        
        // Create form data
        $form_data = array(
            'form_type' => $form_type,
            'family_id' => $family_id,
            'child_id' => $child_id,
            'first_name' => $child_data['first_name'],
            'last_name' => $child_data['last_name'],
            'gender' => $child_data['gender'],
            'dob' => $child_data['dob'],
            'shirt_size' => $child_data['shirt_size'],
            'address' => $family_data['address'],
            'city' => $family_data['city'],
            'state' => $family_data['state'],
            'zip' => $family_data['zip'],
            'phone' => $family_data['phone'],
            'school' => $child_data['school'],
            'medical_issues' => $child_data['medical_issues'],
            'medical_insurance' => $child_data['medical_insurance'],
            'parent_first_name' => $family_data['first_name'],
            'parent_last_name' => $family_data['last_name'],
            'parent_email' => $family_data['email'],
            'emergency_contact' => $family_data['first_name'] . ' ' . $family_data['last_name'],
            'emergency_phone' => $family_data['phone'],
            'social_media_waiver' => $social_media_waiver,
            'disclosure' => $disclosure,
            'signature' => $signature,
        );
        
        // Calculate registration fee
        $global_settings = get_option('srs_global_settings', array());
        $base_pricing = isset($global_settings['base_pricing']) ? $global_settings['base_pricing'] : array();
        $base_price = isset($base_pricing[$form_type]) ? floatval($base_pricing[$form_type]) : 0;
        
        // Apply family discount
        $index = array_search($child_id, $child_ids);
        $child_fee = $base_price;
        
        if ($index > 0) {
            // Apply family discount
            $family_discount = isset($global_settings['family_discount']) ? $global_settings['family_discount'] : array();
            
            if (!empty($family_discount['enabled'])) {
                $discount_percent = 0;
                
                if ($index === 1) {
                    // Second child
                    $discount_percent = floatval($family_discount['second_child']);
                } elseif ($index === 2) {
                    // Third child
                    $discount_percent = floatval($family_discount['third_child']);
                } else {
                    // Fourth or more child
                    $discount_percent = floatval($family_discount['additional_child']);
                }
                
                $discount_amount = $child_fee * ($discount_percent / 100);
                $child_fee -= $discount_amount;
            }
        }
        
        // Save registration
        global $wpdb;
        $table_name = $wpdb->prefix . 'srs_registrations';
        
        $wpdb->insert(
            $table_name,
            array(
                'form_type' => $form_type,
                'first_name' => $child_data['first_name'],
                'last_name' => $child_data['last_name'],
                'form_data' => json_encode($form_data),
                'payment_status' => 'none',
                'payment_amount' => $child_fee,
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%f', '%s')
        );
        
        $registration_ids[] = $wpdb->insert_id;
    }
    
    // Send confirmation email
    $to = $family_data['email'];
    $subject = 'Registration Confirmation - ' . ucfirst($form_type);
    
    $message = "Thank you for registering for " . ucfirst($form_type) . "!\n\n";
    $message .= "The following children have been registered:\n";
    
    foreach ($child_ids as $child_id) {
        $child_data = srs_get_child_data($child_id);
        
        if (!$child_data) {
            continue;
        }
        
        $message .= "- " . $child_data['first_name'] . " " . $child_data['last_name'] . "\n";
    }
    
    $message .= "\nRegistration Date: " . current_time('F j, Y') . "\n\n";
    $message .= "If you have any questions, please contact us.\n\n";
    $message .= "Regards,\nLaurel London Optimist Club";
    
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    wp_mail($to, $subject, $message, $headers);
    
    wp_send_json_success(array(
        'message' => __('Registration submitted successfully!', 'sports-registration'),
        'registration_ids' => $registration_ids,
    ));
}
add_action('wp_ajax_srs_submit_family_registration', 'srs_submit_family_registration_ajax');

/**
 * Modify the registration form to use family account data
 */
function srs_modify_registration_form_for_family_accounts($content, $form_type) {
    // Check if family accounts are enabled
    $global_settings = get_option('srs_global_settings', array());
    $family_accounts_enabled = isset($global_settings['family_accounts_enabled']) ? $global_settings['family_accounts_enabled'] : 0;
    
    if (!$family_accounts_enabled) {
        return $content;
    }
    
    // Check if user is logged in to a family account
    $family_id = srs_get_current_family_id();
    
    if (!$family_id) {
        // Add link to family login
        $login_page = isset($global_settings['family_login_page']) ? $global_settings['family_login_page'] : 0;
        
        if (!empty($login_page)) {
            $login_url = get_permalink($login_page);
            
            $login_message = '<div class="srs-family-login-message">';
            $login_message .= '<p>' . __('Have a family account? <a href="' . esc_url($login_url) . '">Log in</a> to easily register your children.', 'sports-registration') . '</p>';
            $login_message .= '</div>';
            
            return $login_message . $content;
        }
        
        return $content;
    }
    
    // Get family data
    $family_data = srs_get_family_data($family_id);
    
    if (!$family_data) {
        return $content;
    }
    
    // Replace form with family registration options
    ob_start();
    ?>
    <div class="srs-family-registration-container">
        <div class="srs-family-header">
            <h3><?php echo esc_html($family_data['first_name'] . ' ' . $family_data['last_name']); ?> Family</h3>
            <p><?php _e('Select children to register for', 'sports-registration'); ?> <?php echo esc_html(ucfirst($form_type)); ?></p>
        </div>
        
        <?php if (empty($family_data['children'])): ?>
            <div class="srs-empty-state">
                <p><?php _e('No children found in your family account.', 'sports-registration'); ?></p>
                <p><a href="<?php echo esc_url(srs_get_dashboard_url()); ?>" class="srs-button"><?php _e('Go to Family Dashboard', 'sports-registration'); ?></a></p>
            </div>
        <?php else: ?>
            <form id="srs-family-registration-form" class="srs-form">
                <input type="hidden" name="form_type" value="<?php echo esc_attr($form_type); ?>">
                
                <div class="srs-form-field">
                    <label><?php _e('Select Children to Register', 'sports-registration'); ?></label>
                    <div class="srs-children-checkboxes">
                        <?php foreach ($family_data['children'] as $child): ?>
                            <label class="srs-checkbox-label">
                                <input type="checkbox" name="child_ids[]" value="<?php echo esc_attr($child['id']); ?>">
                                <?php echo esc_html($child['first_name'] . ' ' . $child['last_name']); ?>
                                (<?php echo esc_html(ucfirst($child['gender'])); ?>, <?php echo esc_html(date('F j, Y', strtotime($child['dob']))); ?>)
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="srs-social-media-waiver">
                    <label class="srs-checkbox-label">
                        <input type="checkbox" name="social_media_waiver" value="yes">
                        <?php _e('Do you give Laurel London Optimist Club permission to use your child\'s photo (team photo) for use on our website or social media?', 'sports-registration'); ?>
                    </label>
                </div>
                
                <div class="srs-disclosure">
                    <label class="srs-checkbox-label">
                        <input type="checkbox" name="disclosure" value="1" required>
                        <?php echo wp_kses_post($global_settings['disclosure_text'] ?? __('I hereby certify that the information provided is true and accurate.', 'sports-registration')); ?>
                    </label>
                </div>
                
                <div class="srs-signature-section">
                    <label for="srs-signature"><?php _e('Parent/Guardian Signature', 'sports-registration'); ?></label>
                    <div class="srs-signature-pad">
                        <canvas id="srs-signature-canvas" width="400" height="200"></canvas>
                        <input type="hidden" id="srs-signature" name="signature" required>
                        <div class="srs-signature-controls">
                            <button type="button" class="srs-clear-signature"><?php _e('Clear Signature', 'sports-registration'); ?></button>
                        </div>
                    </div>
                </div>
                
                <div id="srs-fee-preview" class="srs-fee-preview" style="display: none;">
                    <h4><?php _e('Registration Fees', 'sports-registration'); ?></h4>
                    <div id="srs-fee-breakdown" class="srs-fee-breakdown"></div>
                    <div id="srs-fee-total" class="srs-fee-total"></div>
                </div>
                
                <div class="srs-form-actions">
                    <button type="submit" class="srs-button"><?php _e('Complete Registration', 'sports-registration'); ?></button>
                    <a href="<?php echo esc_url(srs_get_dashboard_url()); ?>" class="srs-button srs-button-outline"><?php _e('Cancel', 'sports-registration'); ?></a>
                </div>
            </form>
            
            <div class="srs-form-messages" style="display:none;"></div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Initialize signature pad
                    var canvas = document.getElementById('srs-signature-canvas');
                    var signatureInput = document.getElementById('srs-signature');
                    var clearButton = document.querySelector('.srs-clear-signature');
                    
                    var signaturePad = new SignaturePad(canvas, {
                        backgroundColor: 'rgba(255, 255, 255, 0)',
                        penColor: 'rgb(0, 0, 0)'
                    });
                    
                    // Clear signature
                    clearButton.addEventListener('click', function() {
                        signaturePad.clear();
                        signatureInput.value = '';
                    });
                    
                    // Handle child checkboxes
                    var childCheckboxes = document.querySelectorAll('input[name="child_ids[]"]');
                    var feePreview = document.getElementById('srs-fee-preview');
                    var feeBreakdown = document.getElementById('srs-fee-breakdown');
                    var feeTotal = document.getElementById('srs-fee-total');
                    
                    childCheckboxes.forEach(function(checkbox) {
                        checkbox.addEventListener('change', updateFeePreview);
                    });
                    
                    function updateFeePreview() {
                        var formType = document.querySelector('input[name="form_type"]').value;
                        var selectedChildIds = Array.from(childCheckboxes)
                            .filter(function(cb) { return cb.checked; })
                            .map(function(cb) { return cb.value; });
                        
                        if (selectedChildIds.length === 0) {
                            feePreview.style.display = 'none';
                            return;
                        }
                        
                        // Show loading state
                        feeBreakdown.innerHTML = '<div class="srs-loading">Calculating fees...</div>';
                        feeTotal.innerHTML = '';
                        feePreview.style.display = 'block';
                        
                        // Send AJAX request
                        var formData = new FormData();
                        formData.append('action', 'srs_calculate_registration_fees');
                        formData.append('nonce', srs_family_accounts.nonce);
                        formData.append('form_type', formType);
                        formData.append('child_ids', JSON.stringify(selectedChildIds));
                        
                        fetch(srs_family_accounts.ajax_url, {
                            method: 'POST',
                            body: formData,
                        })
                        .then(function(response) {
                            return response.json();
                        })
                        .then(function(data) {
                            if (data.success) {
                                // Update fee breakdown
                                var fees = data.data.fees;
                                
                                var breakdownHtml = '';
                                
                                fees.children.forEach(function(child) {
                                    breakdownHtml += '<div class="srs-fee-child">' +
                                        '<span class="srs-fee-child-name">' + child.name + '</span>' +
                                        '<span class="srs-fee-child-amount">$' + child.fee.toFixed(2) + '</span>' +
                                    '</div>';
                                });
                                
                                feeBreakdown.innerHTML = breakdownHtml;
                                
                                // Update fee total
                                feeTotal.innerHTML = '<span class="srs-fee-total-label">Total:</span>' +
                                    '<span class="srs-fee-total-amount">$' + fees.total.toFixed(2) + '</span>';
                            } else {
                                feePreview.style.display = 'none';
                                alert(data.data.message);
                            }
                        })
                        .catch(function(error) {
                            console.error('Error:', error);
                            feePreview.style.display = 'none';
                            alert('An error occurred. Please try again.');
                        });
                    }
                    
                    // Form submission
                    var form = document.getElementById('srs-family-registration-form');
                    var messages = document.querySelector('.srs-form-messages');
                    
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        
                        var selectedChildIds = Array.from(childCheckboxes)
                            .filter(function(cb) { return cb.checked; })
                            .map(function(cb) { return cb.value; });
                        
                        if (selectedChildIds.length === 0) {
                            messages.innerHTML = '<div class="srs-message srs-message-error">Please select at least one child to register.</div>';
                            messages.style.display = 'block';
                            messages.scrollIntoView({behavior: 'smooth'});
                            return;
                        }
                        
                        // Check signature
                        if (signaturePad.isEmpty()) {
                            messages.innerHTML = '<div class="srs-message srs-message-error">Please sign the form to complete registration.</div>';
                            messages.style.display = 'block';
                            messages.scrollIntoView({behavior: 'smooth'});
                            return;
                        }
                        
                        // Save signature
                        signatureInput.value = signaturePad.toDataURL();
                        
                        // Show loading state
                        var submitButton = form.querySelector('button[type="submit"]');
                        var originalButtonText = submitButton.textContent;
                        submitButton.disabled = true;
                        submitButton.textContent = 'Processing...';
                        
                        // Send AJAX request
                        var formData = new FormData(form);
                        formData.append('action', 'srs_submit_family_registration');
                        formData.append('nonce', srs_family_accounts.nonce);
                        
                        fetch(srs_family_accounts.ajax_url, {
                            method: 'POST',
                            body: formData,
                        })
                        .then(function(response) {
                            return response.json();
                        })
                        .then(function(data) {
                            if (data.success) {
                                messages.innerHTML = '<div class="srs-message srs-message-success">' + data.data.message + '</div>';
                                messages.style.display = 'block';
                                messages.scrollIntoView({behavior: 'smooth'});
                                
                                form.style.display = 'none';
                                
                                // Add dashboard link
                                var dashboardLink = document.createElement('p');
                                dashboardLink.className = 'srs-dashboard-link';
                                dashboardLink.innerHTML = '<a href="<?php echo esc_url(srs_get_dashboard_url()); ?>" class="srs-button">Go to Family Dashboard</a>';
                                messages.appendChild(dashboardLink);
                            } else {
                                messages.innerHTML = '<div class="srs-message srs-message-error">' + data.data.message + '</div>';
                                messages.style.display = 'block';
                                messages.scrollIntoView({behavior: 'smooth'});
                                
                                submitButton.disabled = false;
                                submitButton.textContent = originalButtonText;
                            }
                        })
                        .catch(function(error) {
                            console.error('Error:', error);
                            messages.innerHTML = '<div class="srs-message srs-message-error">An error occurred. Please try again.</div>';
                            messages.style.display = 'block';
                            messages.scrollIntoView({behavior: 'smooth'});
                            
                            submitButton.disabled = false;
                            submitButton.textContent = originalButtonText;
                        });
                    });
                });
            </script>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_filter('srs_registration_form_content', 'srs_modify_registration_form_for_family_accounts', 10, 2);

/**
 * Modify SRS_Forms class to check for family accounts
 */
function srs_modify_forms_class() {
    // Add filter to render_form method
    add_filter('srs_before_render_form', 'srs_check_family_accounts', 10, 1);
}
add_action('init', 'srs_modify_forms_class');

/**
 * Check if family accounts are enabled and user is logged in
 */
function srs_check_family_accounts($form_type) {
    // Check if family accounts are enabled
    $global_settings = get_option('srs_global_settings', array());
    $family_accounts_enabled = isset($global_settings['family_accounts_enabled']) ? $global_settings['family_accounts_enabled'] : 0;
    
    if (!$family_accounts_enabled) {
        return $form_type;
    }
    
    // Check if user is logged in to a family account
    $family_id = srs_get_current_family_id();
    
    if (!$family_id) {
        return $form_type;
    }
    
    // Add filter to form content
    add_filter('srs_form_content', 'srs_replace_form_with_family_registration', 10, 2);
    
    return $form_type;
}

/**
 * Replace standard form with family registration
 */
function srs_replace_form_with_family_registration($content, $form_type) {
    // Get family data
    $family_id = srs_get_current_family_id();
    $family_data = srs_get_family_data($family_id);
    
    if (!$family_data) {
        return $content;
    }
    
    // Replace content with family registration
    ob_start();
    include SRS_PLUGIN_DIR . 'public/partials/srs-family-registration.php';
    return ob_get_clean();
}

<?php
/**
 * Class to handle form creation and processing
 * - Integrated dynamic pricing
 * - Fixed form rendering issues
 * - Improved security
 */
class SRS_Forms {
    private $form_fields = array();
    private $required_fields = array();
    private $form_settings = array();
    private $global_settings = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_form_settings();
        add_shortcode('srs_registration_form', array($this, 'shortcode_output'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Initialize form settings from options
     */
    private function init_form_settings() {
        $this->form_settings = array(
            'basketball' => get_option('srs_basketball_settings', $this->get_default_settings()),
            'soccer' => get_option('srs_soccer_settings', $this->get_default_settings()),
            'cheerleading' => get_option('srs_cheerleading_settings', $this->get_default_settings()),
            'volleyball' => get_option('srs_volleyball_settings', $this->get_default_settings()),
        );
        
        // Get global settings
        $this->global_settings = get_option('srs_global_settings', array(
            'square_enabled' => 0,
            'square_app_id' => '',
            'square_location_id' => '',
            'square_access_token' => '',
            'paypal_enabled' => 0,
            'paypal_client_id' => '',
            'paypal_secret' => '',
            'google_sheets_enabled' => 0,
            'google_sheets_id' => '',
            'disclosure_text' => 'I hereby certify that the information provided is true and accurate.',
        ));
    }
    
    /**
     * Get default settings for a sport
     */
    private function get_default_settings() {
        return array(
            'enabled' => 1,
            'title' => 'Registration Form',
            'price' => '0',
            'required_fields' => array(
                'first_name', 'last_name', 'gender', 'shirt_size', 'address',
                'city', 'state', 'zip', 'phone', 'dob', 'school', 
                'emergency_contact', 'emergency_phone'
            ),
        );
    }
    
    /**
     * Get form fields based on sport type
     */
    private function get_form_fields($form_type) {
        $settings = $this->form_settings[$form_type];
        $fields = array();
        
        $all_fields = $this->get_all_available_fields();
        foreach ($all_fields as $field_id => $field) {
            $field['required'] = in_array($field_id, $settings['required_fields']);
            $fields[$field_id] = $field;
        }
        
        return $fields;
    }
    
    /**
     * Get all available fields
     */
    public function get_all_available_fields() {
        return array(
            'first_name' => array(
                'label' => 'First Name',
                'type' => 'text',
                'placeholder' => 'Enter first name',
            ),
            'last_name' => array(
                'label' => 'Last Name',
                'type' => 'text',
                'placeholder' => 'Enter last name',
            ),
            'gender' => array(
                'label' => 'Gender',
                'type' => 'select',
                'options' => array(
                    '' => 'Select Gender',
                    'male' => 'Male',
                    'female' => 'Female',
                ),
            ),
            'shirt_size' => array(
                'label' => 'Shirt Size',
                'type' => 'select',
                'options' => array(
                    '' => 'Select Shirt Size',
                    'YXS' => 'Youth Extra Small (YXS)',
                    'YS' => 'Youth Small (YS)',
                    'YM' => 'Youth Medium (YM)',
                    'YL' => 'Youth Large (YL)',
                    'YXL' => 'Youth Extra Large (YXL)',
                    'AS' => 'Adult Small (AS)',
                    'AM' => 'Adult Medium (AM)',
                    'AL' => 'Adult Large (AL)',
                    'AXL' => 'Adult Extra Large (AXL)',
                    'A2XL' => 'Adult 2XL (A2XL)',
                ),
            ),
            'address' => array(
                'label' => 'Physical Address',
                'type' => 'text',
                'placeholder' => 'Enter physical address',
            ),
            'city' => array(
                'label' => 'City',
                'type' => 'text',
                'placeholder' => 'Enter city',
            ),
            'state' => array(
                'label' => 'State',
                'type' => 'text',
                'placeholder' => 'Enter state',
            ),
            'zip' => array(
                'label' => 'Zip Code',
                'type' => 'text',
                'placeholder' => 'Enter zip code',
            ),
            'phone' => array(
                'label' => 'Preferred Phone Number',
                'type' => 'tel',
                'placeholder' => 'Enter phone number',
            ),
            'dob' => array(
                'label' => 'Date of Birth',
                'type' => 'date',
                'special' => 'age_calculation',
            ),
            'school' => array(
                'label' => 'School',
                'type' => 'text',
                'placeholder' => 'Enter school name',
            ),
            'medical_issues' => array(
                'label' => 'Medical Issues',
                'type' => 'textarea',
                'placeholder' => 'List any medical issues or allergies',
            ),
            'medical_insurance' => array(
                'label' => 'Medical Insurance',
                'type' => 'radio',
                'options' => array(
                    'yes' => 'Yes',
                    'no' => 'No',
                ),
            ),
            'siblings' => array(
                'label' => 'Siblings (Name and Age)',
                'type' => 'textarea',
                'placeholder' => 'Enter siblings name and age',
            ),
            'emergency_contact' => array(
                'label' => 'Emergency Contact Name',
                'type' => 'text',
                'placeholder' => 'Enter emergency contact name',
            ),
            'emergency_phone' => array(
                'label' => 'Emergency Contact Phone',
                'type' => 'tel',
                'placeholder' => 'Enter emergency contact phone',
            ),
            'social_media_waiver' => array(
                'label' => 'Do you give Laurel London Optimist Club permission to use your child\'s photo (team photo) for use on our website or social media?',
                'type' => 'radio',
                'options' => array(
                    'yes' => 'Yes',
                    'no' => 'No',
                ),
            ),
            'disclosure' => array(
                'label' => $this->global_settings['disclosure_text'],
                'type' => 'checkbox',
                'required' => true,
            ),
            'signature' => array(
                'label' => 'Parent/Guardian Signature',
                'type' => 'signature',
                'required' => true,
            ),
        );
    }
    
    /**
     * Render form for a specific sport with dynamic pricing support
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
        
        ob_start();
        ?>
        <div class="srs-registration-form-container" data-form-type="<?php echo esc_attr($form_type); ?>">
            <h2><?php echo esc_html($settings['title']); ?></h2>
            
            <form id="srs-registration-form-<?php echo esc_attr($form_type); ?>" class="srs-registration-form">
                <input type="hidden" name="form_type" value="<?php echo esc_attr($form_type); ?>">
                <input type="hidden" name="base_price" value="<?php echo esc_attr($base_price); ?>">
                
                <?php foreach ($fields as $field_id => $field): ?>
                    <?php $this->render_field($field_id, $field); ?>
                <?php endforeach; ?>
                
                <?php if ($base_price > 0): ?>
                    <div class="srs-payment-section">
                        <h3 class="srs-fee-title">Registration Fee</h3>
                        <div class="srs-fee-calculation">
                            <div class="srs-fee-base">
                                <span class="srs-fee-label">Base Registration Fee:</span>
                                <span class="srs-fee-amount">$<?php echo number_format($base_price, 2); ?></span>
                            </div>
                            <div class="srs-fee-discount" style="display: none;">
                                <span class="srs-fee-label">Family Discount:</span>
                                <span class="srs-fee-amount">$0.00</span>
                            </div>
                            <div class="srs-fee-total">
                                <span class="srs-fee-label">Total Due:</span>
                                <span class="srs-fee-amount">$<?php echo number_format($base_price, 2); ?></span>
                            </div>
                        </div>
                        <input type="hidden" name="payment_amount" value="<?php echo esc_attr($base_price); ?>" data-base-price="<?php echo esc_attr($base_price); ?>">
                        
                        <?php if (!empty($this->global_settings['square_enabled'])): ?>
                            <div class="srs-payment-option">
                                <label>
                                    <input type="radio" name="payment_method" value="square" checked>
                                    Pay with Credit Card (Square)
                                </label>
                                <div id="square-payment-form" class="srs-payment-form" style="display:none;">
                                    <!-- Square payment form will be injected here via JS -->
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($this->global_settings['paypal_enabled'])): ?>
                            <div class="srs-payment-option">
                                <label>
                                    <input type="radio" name="payment_method" value="paypal">
                                    Pay with PayPal
                                </label>
                                <div id="paypal-payment-form" class="srs-payment-form" style="display:none;">
                                    <!-- PayPal button will be injected here via JS -->
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="srs-form-submit">
                    <button type="submit" class="srs-submit-button">Submit Registration</button>
                </div>
            </form>
            
            <div class="srs-form-messages" style="display:none;"></div>
            
            <div class="srs-family-info-note" style="display:none;">
                <p>Family discounts are automatically applied when you register multiple children from the same family.</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render a single form field
     */
    private function render_field($field_id, $field) {
        $required = !empty($field['required']) ? 'required' : '';
        $required_mark = !empty($field['required']) ? '<span class="srs-required">*</span>' : '';
        
        echo '<div class="srs-form-field srs-field-' . esc_attr($field_id) . '">';
        echo '<label for="srs-' . esc_attr($field_id) . '">' . wp_kses_post($field['label']) . ' ' . $required_mark . '</label>';
        
        switch ($field['type']) {
            case 'text':
            case 'email':
            case 'tel':
            case 'date':
                echo '<input type="' . esc_attr($field['type']) . '" id="srs-' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" placeholder="' . esc_attr($field['placeholder']) . '" ' . $required . '>';
                break;
                
            case 'textarea':
                echo '<textarea id="srs-' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" placeholder="' . esc_attr($field['placeholder']) . '" ' . $required . '></textarea>';
                break;
                
            case 'select':
                echo '<select id="srs-' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" ' . $required . '>';
                foreach ($field['options'] as $value => $label) {
                    echo '<option value="' . esc_attr($value) . '">' . esc_html($label) . '</option>';
                }
                echo '</select>';
                break;
                
            case 'radio':
                echo '<div class="srs-radio-group">';
                foreach ($field['options'] as $value => $label) {
                    echo '<label class="srs-radio-label">';
                    echo '<input type="radio" name="' . esc_attr($field_id) . '" value="' . esc_attr($value) . '" ' . $required . '>';
                    echo esc_html($label);
                    echo '</label>';
                }
                echo '</div>';
                break;
                
            case 'checkbox':
                echo '<label class="srs-checkbox-label">';
                echo '<input type="checkbox" id="srs-' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" value="1" ' . $required . '>';
                echo wp_kses_post($field['label']);
                echo '</label>';
                break;
                
            case 'signature':
                echo '<div class="srs-signature-pad">';
                echo '<canvas id="srs-signature-canvas" width="400" height="200"></canvas>';
                echo '<input type="hidden" id="srs-' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" ' . $required . '>';
                echo '<div class="srs-signature-controls">';
                echo '<button type="button" class="srs-clear-signature">Clear Signature</button>';
                echo '</div>';
                echo '</div>';
                break;
        }
        
        echo '</div>';
    }
    
    /**
     * Process form submission
     */
    public function process_form_submission() {
        // Verify nonce
        check_ajax_referer('srs_form_nonce', 'security');
        
        // Sanitize form data
        $form_data = $this->sanitize_form_data($_POST);
        $form_type = sanitize_text_field($form_data['form_type']);
        
        // Validate form data
        $settings = $this->form_settings[$form_type];
        $fields = $this->get_form_fields($form_type);
        
        // Validate required fields
        foreach ($fields as $field_id => $field) {
            if (!empty($field['required']) && empty($form_data[$field_id])) {
                wp_send_json_error(array(
                    'message' => 'Please fill in all required fields.',
                ));
                return;
            }
        }
        
        // Apply filters before processing (for family discounts, etc.)
        $form_data = apply_filters('srs_before_process_registration', $form_data);
        
        // Process payment if needed
        if (!empty($settings['price']) && floatval($settings['price']) > 0) {
            $payment_processor = new SRS_Payments();
            $payment_result = $payment_processor->process_payment($form_data);
            
            if (is_wp_error($payment_result)) {
                wp_send_json_error(array(
                    'message' => 'Payment failed: ' . $payment_result->get_error_message(),
                ));
                return;
            }
            
            // Add payment info to form data
            $form_data['payment_id'] = $payment_result['id'];
            $form_data['payment_status'] = $payment_result['status'];
        }
        
        // Save to database
        $registration_id = $this->save_registration($form_data);
        
        if (!$registration_id) {
            wp_send_json_error(array(
                'message' => 'Failed to save registration. Please try again.',
            ));
            return;
        }
        
        // Trigger action for post-registration processes
        do_action('srs_after_registration_submitted', $registration_id, $form_data);
        
        // Sync to Google Sheets if enabled
        if (!empty($this->global_settings['google_sheets_enabled']) && !empty($this->global_settings['google_sheets_id'])) {
            $sheets_sync = new SRS_Google_Sheet();
            $sheets_sync->add_row($form_data);
        }
        
        // Send confirmation email
        $this->send_confirmation_email($form_data);
        
        wp_send_json_success(array(
            'message' => 'Registration submitted successfully!',
            'registration_id' => $registration_id,
        ));
    }
    
    /**
     * Sanitize form data
     */
    private function sanitize_form_data($data) {
        $sanitized = array();
        
        foreach ($data as $key => $value) {
            if ($key === 'form_data' || $key === 'signature') {
                // These are already processed separately
                $sanitized[$key] = $value;
            } elseif (is_array($value)) {
                $sanitized[$key] = array_map('sanitize_text_field', $value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Save registration to database with improved error handling
     */
    private function save_registration($form_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'srs_registrations';
        
        // Check if table exists first
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            error_log("Sports Registration System: Registrations table does not exist: $table_name");
            return false;
        }
        
        // Prepare data for insertion
        $data = array(
            'form_type' => sanitize_text_field($form_data['form_type']),
            'first_name' => sanitize_text_field($form_data['first_name']),
            'last_name' => sanitize_text_field($form_data['last_name']),
            'form_data' => json_encode($form_data),
            'payment_status' => isset($form_data['payment_status']) ? sanitize_text_field($form_data['payment_status']) : 'none',
            'payment_id' => isset($form_data['payment_id']) ? sanitize_text_field($form_data['payment_id']) : '',
            'payment_amount' => isset($form_data['payment_amount']) ? floatval($form_data['payment_amount']) : 0,
            'created_at' => current_time('mysql'),
        );
        
        // Add family_id if present
        if (isset($form_data['family_id']) && !empty($form_data['family_id'])) {
            $data['family_id'] = intval($form_data['family_id']);
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
     * Send confirmation email
     */
    private function send_confirmation_email($form_data) {
        $to = sanitize_email($form_data['email'] ?? '');
        
        if (empty($to) || !is_email($to)) {
            return;
        }
        
        $subject = 'Registration Confirmation - ' . ucfirst($form_data['form_type']);
        
        $message = "Thank you for registering for " . ucfirst($form_data['form_type']) . "!\n\n";
        $message .= "Registration Details:\n";
        $message .= "Name: " . sanitize_text_field($form_data['first_name']) . " " . sanitize_text_field($form_data['last_name']) . "\n";
        $message .= "Registration Date: " . current_time('F j, Y') . "\n\n";
        
        if (!empty($form_data['payment_status']) && $form_data['payment_status'] == 'paid') {
            $message .= "Payment Status: Paid\n";
            $message .= "Payment ID: " . sanitize_text_field($form_data['payment_id']) . "\n";
            $message .= "Amount: $" . number_format(floatval($form_data['payment_amount']), 2) . "\n\n";
        }
        
        $message .= "If you have any questions, please contact us.\n\n";
        $message .= "Regards,\nLaurel London Optimist Club";
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        wp_mail($to, $subject, $message, $headers);
    }
    
    /**
     * Shortcode handler
     */
    public function shortcode_output($atts) {
        $atts = shortcode_atts(array(
            'type' => 'basketball',
        ), $atts, 'srs_registration_form');
        
        return $this->render_form($atts['type']);
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Main registration form styles
        wp_enqueue_style(
            'srs-public', 
            SRS_PLUGIN_URL . 'public/css/srs-public.css', 
            array(), 
            SRS_PLUGIN_VERSION
        );
        
        // Mobile responsive styles
        wp_enqueue_style(
            'srs-mobile', 
            SRS_PLUGIN_URL . 'public/css/srs-mobile-responsive.css', 
            array('srs-public'), 
            SRS_PLUGIN_VERSION
        );
        
        // Family discount styles
        wp_enqueue_style(
            'srs-pricing', 
            SRS_PLUGIN_URL . 'public/css/srs-pricing.css', 
            array('srs-public'), 
            SRS_PLUGIN_VERSION
        );
        
        // Signature Pad library
        wp_enqueue_script(
            'signature-pad', 
            SRS_PLUGIN_URL . 'public/js/signature_pad.min.js', 
            array(), 
            '2.3.2', 
            true
        );
        
        // Main registration script
        wp_register_script(
            'srs-public', 
            SRS_PLUGIN_URL . 'public/js/srs-public.js', 
            array('jquery', 'signature-pad'), 
            SRS_PLUGIN_VERSION, 
            true
        );
        
        // Family discount script
        wp_register_script(
            'srs-family-discount', 
            SRS_PLUGIN_URL . 'public/js/srs-family-discount.js', 
            array('jquery', 'srs-public'), 
            SRS_PLUGIN_VERSION, 
            true
        );
        
        // Localize script with AJAX URL and nonces
        wp_localize_script('srs-public', 'srs_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'form_nonce' => wp_create_nonce('srs_form_nonce'),
            'discount_nonce' => wp_create_nonce('srs_family_discount_nonce'),
        ));
        
        // Enqueue the main script
        wp_enqueue_script('srs-public');
        wp_enqueue_script('srs-family-discount');
    }
}
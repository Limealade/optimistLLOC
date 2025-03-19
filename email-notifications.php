<?php
/**
 * Email Notification System
 *
 * Handles all automated emails sent by the system
 */

class SRS_Email_Notifications {
    
    private $from_email;
    private $from_name;
    private $email_templates;
    
    public function __construct() {
        // Initialize email settings
        $this->init_settings();
        
        // Register hooks for automatic emails
        add_action('srs_after_registration_submitted', array($this, 'send_registration_confirmation'), 10, 2);
        add_action('srs_after_family_account_created', array($this, 'send_account_creation_notification'), 10, 1);
        add_action('srs_after_child_added', array($this, 'send_child_added_notification'), 10, 2);
        add_action('srs_after_payment_completed', array($this, 'send_payment_confirmation'), 10, 2);
        
        // Admin notification hooks
        add_action('srs_after_registration_submitted', array($this, 'notify_admin_new_registration'), 10, 2);
        
        // Reminder emails
        add_action('srs_send_registration_reminder', array($this, 'send_registration_reminder'));
        
        // Schedule reminder emails
        if (!wp_next_scheduled('srs_send_registration_reminder')) {
            wp_schedule_event(time(), 'daily', 'srs_send_registration_reminder');
        }
        
        // Email customization options in admin
        add_action('admin_init', array($this, 'register_email_settings'));
    }
    
    /**
     * Initialize email settings
     */
    private function init_settings() {
        $options = get_option('srs_email_settings', array());
        
        // Set default from email and name
        $this->from_email = isset($options['from_email']) ? $options['from_email'] : get_option('admin_email');
        $this->from_name = isset($options['from_name']) ? $options['from_name'] : get_bloginfo('name');
        
        // Load email templates
        $this->email_templates = array(
            'registration_confirmation' => isset($options['registration_confirmation_template']) ? $options['registration_confirmation_template'] : $this->get_default_template('registration_confirmation'),
            'account_creation' => isset($options['account_creation_template']) ? $options['account_creation_template'] : $this->get_default_template('account_creation'),
            'child_added' => isset($options['child_added_template']) ? $options['child_added_template'] : $this->get_default_template('child_added'),
            'payment_confirmation' => isset($options['payment_confirmation_template']) ? $options['payment_confirmation_template'] : $this->get_default_template('payment_confirmation'),
            'registration_reminder' => isset($options['registration_reminder_template']) ? $options['registration_reminder_template'] : $this->get_default_template('registration_reminder'),
        );
    }
    
    /**
     * Register email settings
     */
    public function register_email_settings() {
        register_setting(
            'srs_email_settings',
            'srs_email_settings',
            array($this, 'validate_email_settings')
        );
        
        add_settings_section(
            'srs_email_settings_section',
            __('Email Notification Settings', 'sports-registration'),
            array($this, 'render_email_settings_section'),
            'srs-email-settings'
        );
        
        add_settings_field(
            'srs_email_from',
            __('From Email & Name', 'sports-registration'),
            array($this, 'render_from_field'),
            'srs-email-settings',
            'srs_email_settings_section'
        );
        
        add_settings_field(
            'srs_registration_confirmation_template',
            __('Registration Confirmation Email', 'sports-registration'),
            array($this, 'render_template_field'),
            'srs-email-settings',
            'srs_email_settings_section',
            array('template_key' => 'registration_confirmation')
        );
        
        add_settings_field(
            'srs_account_creation_template',
            __('Account Creation Email', 'sports-registration'),
            array($this, 'render_template_field'),
            'srs-email-settings',
            'srs_email_settings_section',
            array('template_key' => 'account_creation')
        );
        
        add_settings_field(
            'srs_child_added_template',
            __('Child Added Email', 'sports-registration'),
            array($this, 'render_template_field'),
            'srs-email-settings',
            'srs_email_settings_section',
            array('template_key' => 'child_added')
        );
        
        add_settings_field(
            'srs_payment_confirmation_template',
            __('Payment Confirmation Email', 'sports-registration'),
            array($this, 'render_template_field'),
            'srs-email-settings',
            'srs_email_settings_section',
            array('template_key' => 'payment_confirmation')
        );
        
        add_settings_field(
            'srs_registration_reminder_template',
            __('Registration Reminder Email', 'sports-registration'),
            array($this, 'render_template_field'),
            'srs-email-settings',
            'srs_email_settings_section',
            array('template_key' => 'registration_reminder')
        );
    }
    
    /**
     * Render email settings section
     */
    public function render_email_settings_section() {
        echo '<p>' . __('Configure email notifications sent by the registration system. You can customize the email templates using the following placeholders:', 'sports-registration') . '</p>';
        
        echo '<ul class="srs-placeholder-list">';
        echo '<li><code>{site_name}</code> - ' . __('Your website name', 'sports-registration') . '</li>';
        echo '<li><code>{parent_name}</code> - ' . __('Parent/guardian full name', 'sports-registration') . '</li>';
        echo '<li><code>{child_name}</code> - ' . __('Child\'s full name', 'sports-registration') . '</li>';
        echo '<li><code>{sport_type}</code> - ' . __('Sport type (Basketball, Soccer, etc.)', 'sports-registration') . '</li>';
        echo '<li><code>{registration_date}</code> - ' . __('Date of registration', 'sports-registration') . '</li>';
        echo '<li><code>{payment_amount}</code> - ' . __('Payment amount', 'sports-registration') . '</li>';
        echo '<li><code>{payment_status}</code> - ' . __('Payment status', 'sports-registration') . '</li>';
        echo '<li><code>{dashboard_url}</code> - ' . __('Family dashboard URL', 'sports-registration') . '</li>';
        echo '<li><code>{login_url}</code> - ' . __('Family login URL', 'sports-registration') . '</li>';
        echo '</ul>';
    }
    
    /**
     * Render from email and name fields
     */
    public function render_from_field() {
        $options = get_option('srs_email_settings', array());
        $from_email = isset($options['from_email']) ? $options['from_email'] : get_option('admin_email');
        $from_name = isset($options['from_name']) ? $options['from_name'] : get_bloginfo('name');
        
        ?>
        <div class="srs-email-field">
            <label for="srs_from_email"><?php _e('From Email:', 'sports-registration'); ?></label>
            <input type="email" id="srs_from_email" name="srs_email_settings[from_email]" value="<?php echo esc_attr($from_email); ?>" class="regular-text">
        </div>
        
        <div class="srs-email-field">
            <label for="srs_from_name"><?php _e('From Name:', 'sports-registration'); ?></label>
            <input type="text" id="srs_from_name" name="srs_email_settings[from_name]" value="<?php echo esc_attr($from_name); ?>" class="regular-text">
        </div>
        <?php
    }
    
    /**
     * Render template field
     */
    public function render_template_field($args) {
        $template_key = $args['template_key'];
        $options = get_option('srs_email_settings', array());
        $template = isset($options[$template_key . '_template']) ? $options[$template_key . '_template'] : $this->get_default_template($template_key);
        
        ?>
        <div class="srs-email-template-field">
            <div class="srs-email-field">
                <label for="srs_<?php echo $template_key; ?>_subject"><?php _e('Subject:', 'sports-registration'); ?></label>
                <input type="text" id="srs_<?php echo $template_key; ?>_subject" name="srs_email_settings[<?php echo $template_key; ?>_subject]" value="<?php echo esc_attr($template['subject']); ?>" class="regular-text">
            </div>
            
            <div class="srs-email-field">
                <label for="srs_<?php echo $template_key; ?>_body"><?php _e('Body:', 'sports-registration'); ?></label>
                <textarea id="srs_<?php echo $template_key; ?>_body" name="srs_email_settings[<?php echo $template_key; ?>_body]" rows="10" class="large-text"><?php echo esc_textarea($template['body']); ?></textarea>
            </div>
        </div>
        <?php
    }
    
    /**
     * Validate email settings
     */
    public function validate_email_settings($input) {
        $output = array();
        
        // Validate from email and name
        $output['from_email'] = sanitize_email($input['from_email']);
        $output['from_name'] = sanitize_text_field($input['from_name']);
        
        // Validate templates
        $template_keys = array(
            'registration_confirmation',
            'account_creation',
            'child_added',
            'payment_confirmation',
            'registration_reminder',
        );
        
        foreach ($template_keys as $key) {
            if (isset($input[$key . '_subject'])) {
                $output[$key . '_template'] = array(
                    'subject' => sanitize_text_field($input[$key . '_subject']),
                    'body' => wp_kses_post($input[$key . '_body']),
                );
            }
        }
        
        return $output;
    }
    
    /**
     * Get default email template
     */
    private function get_default_template($template_key) {
        $templates = array(
            'registration_confirmation' => array(
                'subject' => '{sport_type} Registration Confirmation',
                'body' => "Dear {parent_name},\n\nThank you for registering {child_name} for {sport_type}!\n\nRegistration Details:\n- Sport: {sport_type}\n- Child: {child_name}\n- Registration Date: {registration_date}\n- Payment Status: {payment_status}\n\nIf you have any questions, please don't hesitate to contact us.\n\nBest regards,\n{site_name}",
            ),
            'account_creation' => array(
                'subject' => 'Welcome to {site_name} - Family Account Created',
                'body' => "Dear {parent_name},\n\nThank you for creating a family account at {site_name}!\n\nYour account has been successfully created. You can now add children to your family profile and register for available sports.\n\nTo access your family dashboard, please visit:\n{dashboard_url}\n\nIf you have any questions, please don't hesitate to contact us.\n\nBest regards,\n{site_name}",
            ),
            'child_added' => array(
                'subject' => 'Child Profile Added to Your Family Account',
                'body' => "Dear {parent_name},\n\nA new child profile for {child_name} has been added to your family account at {site_name}.\n\nYou can now register {child_name} for available sports through your family dashboard:\n{dashboard_url}\n\nIf you have any questions, please don't hesitate to contact us.\n\nBest regards,\n{site_name}",
            ),
            'payment_confirmation' => array(
                'subject' => 'Payment Confirmation - {sport_type} Registration',
                'body' => "Dear {parent_name},\n\nThank you for your payment of {payment_amount} for {child_name}'s registration in {sport_type}.\n\nPayment Details:\n- Amount: {payment_amount}\n- Status: {payment_status}\n- Date: {registration_date}\n\nIf you have any questions, please don't hesitate to contact us.\n\nBest regards,\n{site_name}",
            ),
            'registration_reminder' => array(
                'subject' => 'Registration Reminder - {sport_type}',
                'body' => "Dear {parent_name},\n\nThis is a friendly reminder that registration for {sport_type} is currently open. If you haven't registered your child yet, there's still time!\n\nTo register, please visit:\n{dashboard_url}\n\nRegistration closes soon, so don't delay.\n\nBest regards,\n{site_name}",
            ),
        );
        
        return $templates[$template_key] ?? array('subject' => '', 'body' => '');
    }
    
    /**
     * Send registration confirmation email
     */
    public function send_registration_confirmation($registration_id, $form_data) {
        // Get registration details
        global $wpdb;
        $table_name = $wpdb->prefix . 'srs_registrations';
        
        $registration = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $registration_id
            )
        );
        
        if (!$registration) {
            return;
        }
        
        // Get parent email
        $parent_email = isset($form_data['parent_email']) ? $form_data['parent_email'] : '';
        
        if (empty($parent_email)) {
            return;
        }
        
        // Prepare replacement data
        $replacement_data = array(
            'site_name' => get_bloginfo('name'),
            'parent_name' => isset($form_data['parent_first_name']) && isset($form_data['parent_last_name']) ? $form_data['parent_first_name'] . ' ' . $form_data['parent_last_name'] : '',
            'child_name' => $registration->first_name . ' ' . $registration->last_name,
            'sport_type' => ucfirst($registration->form_type),
            'registration_date' => date_i18n(get_option('date_format'), strtotime($registration->created_at)),
            'payment_amount' => $registration->payment_amount ? '$' . number_format($registration->payment_amount, 2) : 'N/A',
            'payment_status' => ucfirst($registration->payment_status),
            'dashboard_url' => $this->get_dashboard_url(),
            'login_url' => $this->get_login_url(),
        );
        
        // Get email template
        $template = $this->email_templates['registration_confirmation'];
        
        // Send email
        $this->send_email($parent_email, $template, $replacement_data);
    }
    
    /**
     * Send account creation notification
     */
    public function send_account_creation_notification($family_id) {
        // Get family data
        $family = get_post($family_id);
        
        if (!$family || $family->post_type !== 'srs_family') {
            return;
        }
        
        $first_name = get_post_meta($family_id, 'first_name', true);
        $last_name = get_post_meta($family_id, 'last_name', true);
        $email = get_post_meta($family_id, 'email', true);
        
        if (empty($email)) {
            return;
        }
        
        // Prepare replacement data
        $replacement_data = array(
            'site_name' => get_bloginfo('name'),
            'parent_name' => $first_name . ' ' . $last_name,
            'dashboard_url' => $this->get_dashboard_url(),
            'login_url' => $this->get_login_url(),
        );
        
        // Get email template
        $template = $this->email_templates['account_creation'];
        
        // Send email
        $this->send_email($email, $template, $replacement_data);
    }
    
    /**
     * Send child added notification
     */
    public function send_child_added_notification($child_id, $family_id) {
        // Get family data
        $family = get_post($family_id);
        
        if (!$family || $family->post_type !== 'srs_family') {
            return;
        }
        
        $family_first_name = get_post_meta($family_id, 'first_name', true);
        $family_last_name = get_post_meta($family_id, 'last_name', true);
        $email = get_post_meta($family_id, 'email', true);
        
        if (empty($email)) {
            return;
        }
        
        // Get child data
        $child = get_post($child_id);
        
        if (!$child || $child->post_type !== 'srs_child') {
            return;
        }
        
        $child_first_name = get_post_meta($child_id, 'first_name', true);
        $child_last_name = get_post_meta($child_id, 'last_name', true);
        
        // Prepare replacement data
        $replacement_data = array(
            'site_name' => get_bloginfo('name'),
            'parent_name' => $family_first_name . ' ' . $family_last_name,
            'child_name' => $child_first_name . ' ' . $child_last_name,
            'dashboard_url' => $this->get_dashboard_url(),
            'login_url' => $this->get_login_url(),
        );
        
        // Get email template
        $template = $this->email_templates['child_added'];
        
        // Send email
        $this->send_email($email, $template, $replacement_data);
    }
    
    /**
     * Send payment confirmation
     */
    public function send_payment_confirmation($registration_id, $payment_data) {
        // Get registration details
        global $wpdb;
        $table_name = $wpdb->prefix . 'srs_registrations';
        
        $registration = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $registration_id
            )
        );
        
        if (!$registration) {
            return;
        }
        
        $form_data = json_decode($registration->form_data, true);
        
        // Get parent email
        $parent_email = isset($form_data['parent_email']) ? $form_data['parent_email'] : '';
        
        if (empty($parent_email)) {
            return;
        }
        
        // Prepare replacement data
        $replacement_data = array(
            'site_name' => get_bloginfo('name'),
            'parent_name' => isset($form_data['parent_first_name']) && isset($form_data['parent_last_name']) ? $form_data['parent_first_name'] . ' ' . $form_data['parent_last_name'] : '',
            'child_name' => $registration->first_name . ' ' . $registration->last_name,
            'sport_type' => ucfirst($registration->form_type),
            'registration_date' => date_i18n(get_option('date_format'), strtotime($registration->created_at)),
            'payment_amount' => $registration->payment_amount ? '$' . number_format($registration->payment_amount, 2) : 'N/A',
            'payment_status' => ucfirst($registration->payment_status),
            'dashboard_url' => $this->get_dashboard_url(),
            'login_url' => $this->get_login_url(),
        );
        
        // Get email template
        $template = $this->email_templates['payment_confirmation'];
        
        // Send email
        $this->send_email($parent_email, $template, $replacement_data);
    }
    
    /**
     * Notify admin of new registration
     */
    public function notify_admin_new_registration($registration_id, $form_data) {
        // Get admin email
        $admin_email = get_option('admin_email');
        
        // Get registration details
        global $wpdb;
        $table_name = $wpdb->prefix . 'srs_registrations';
        
        $registration = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %d",
                $registration_id
            )
        );
        
        if (!$registration) {
            return;
        }
        
        // Prepare email subject and body
        $subject = sprintf(
            __('New %s Registration: %s %s', 'sports-registration'),
            ucfirst($registration->form_type),
            $registration->first_name,
            $registration->last_name
        );
        
        $body = sprintf(
            __("A new registration has been submitted:\n\nSport: %s\nChild: %s %s\nParent: %s\nEmail: %s\nPhone: %s\nPayment Status: %s\n\nView full details in the admin dashboard.", 'sports-registration'),
            ucfirst($registration->form_type),
            $registration->first_name,
            $registration->last_name,
            isset($form_data['parent_first_name']) && isset($form_data['parent_last_name']) ? $form_data['parent_first_name'] . ' ' . $form_data['parent_last_name'] : '',
            isset($form_data['parent_email']) ? $form_data['parent_email'] : '',
            isset($form_data['phone']) ? $form_data['phone'] : '',
            ucfirst($registration->payment_status)
        );
        
        // Set headers
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $this->from_name . ' <' . $this->from_email . '>',
        );
        
        // Send email
        wp_mail($admin_email, $subject, $body, $headers);
    }
    
    /**
     * Send registration reminder
     */
    public function send_registration_reminder() {
        // Check if there are active registration seasons
        $active_seasons = srs_get_active_seasons();
        
        if (empty($active_seasons)) {
            return;
        }
        
        // Get all family accounts
        $families = get_posts(array(
            'post_type' => 'srs_family',
            'posts_per_page' => -1,
        ));
        
        if (empty($families)) {
            return;
        }
        
        // Send reminder to each family
        foreach ($families as $family) {
            $email = get_post_meta($family->ID, 'email', true);
            $first_name = get_post_meta($family->ID, 'first_name', true);
            $last_name = get_post_meta($family->ID, 'last_name', true);
            
            if (empty($email)) {
                continue;
            }
            
            // Get active sports
            $active_sports = array();
            
            foreach ($active_seasons as $season) {
                $sport_types = get_post_meta($season->ID, 'sport_types', true);
                
                if (!empty($sport_types) && is_array($sport_types)) {
                    $active_sports = array_merge($active_sports, $sport_types);
                }
            }
            
            $active_sports = array_unique($active_sports);
            
            if (empty($active_sports)) {
                continue;
            }
            
            // Send reminder for each sport
            foreach ($active_sports as $sport_type) {
                // Check if already registered for this sport
                $children = get_post_meta($family->ID, 'children', true);
                
                if (empty($children) || !is_array($children)) {
                    continue;
                }
                
                $has_unregistered_children = false;
                
                foreach ($children as $child_id) {
                    // Check if child is already registered for this sport this season
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'srs_registrations';
                    
                    $registration_count = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT COUNT(*) FROM $table_name WHERE form_data LIKE %s AND form_type = %s AND created_at >= %s",
                            '%"child_id":"' . $child_id . '"%',
                            $sport_type,
                            date('Y-01-01') // Current year
                        )
                    );
                    
                    if ($registration_count == 0) {
                        $has_unregistered_children = true;
                        break;
                    }
                }
                
                if (!$has_unregistered_children) {
                    continue;
                }
                
                // Prepare replacement data
                $replacement_data = array(
                    'site_name' => get_bloginfo('name'),
                    'parent_name' => $first_name . ' ' . $last_name,
                    'sport_type' => ucfirst($sport_type),
                    'dashboard_url' => $this->get_dashboard_url(),
                    'login_url' => $this->get_login_url(),
                );
                
                // Get email template
                $template = $this->email_templates['registration_reminder'];
                
                // Send email
                $this->send_email($email, $template, $replacement_data);
            }
        }
    }
    
    /**
     * Send email
     */
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
    
    /**
     * Get dashboard URL
     */
    private function get_dashboard_url() {
        $settings = get_option('srs_global_settings', array());
        $dashboard_page = isset($settings['parent_dashboard_page']) ? $settings['parent_dashboard_page'] : 0;
        
        if (empty($dashboard_page)) {
            return home_url();
        }
        
        return get_permalink($dashboard_page);
    }
    
    /**
     * Get login URL
     */
    private function get_login_url() {
        $settings = get_option('srs_global_settings', array());
        $login_page = isset($settings['family_login_page']) ? $settings['family_login_page'] : 0;
        
        if (empty($login_page)) {
            return home_url();
        }
        
        return get_permalink($login_page);
    }
}

// Initialize email notifications
new SRS_Email_Notifications();

<?php
/**
 * Family Account System
 * 
 * Allows parents to create accounts, manage children, and streamline future registrations
 */

class SRS_Family_Accounts {
    
    public function __construct() {
        // Initialize database tables
        add_action('init', array($this, 'register_post_types'));
        add_action('admin_init', array($this, 'register_family_settings'));
        
        // Handle login/registration
        add_action('wp_ajax_nopriv_srs_parent_login', array($this, 'ajax_parent_login'));
        add_action('wp_ajax_nopriv_srs_parent_register', array($this, 'ajax_parent_register'));
        add_action('wp_ajax_srs_parent_logout', array($this, 'ajax_parent_logout'));
        
        // Handle child management
        add_action('wp_ajax_srs_add_child', array($this, 'ajax_add_child'));
        add_action('wp_ajax_srs_update_child', array($this, 'ajax_update_child'));
        add_action('wp_ajax_srs_remove_child', array($this, 'ajax_remove_child'));
        
        // Registration period handling
        add_action('admin_init', array($this, 'register_season_settings'));
        add_filter('srs_available_registration_forms', array($this, 'filter_available_forms_by_date'), 10, 1);
        
        // Frontend shortcodes
        add_shortcode('srs_family_dashboard', array($this, 'render_family_dashboard'));
        add_shortcode('srs_family_login', array($this, 'render_family_login_form'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Register custom post types for family accounts and children
     */
    public function register_post_types() {
        // Parent/Family Accounts
        register_post_type('srs_family', array(
            'labels' => array(
                'name' => 'Family Accounts',
                'singular_name' => 'Family Account',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'sports-registration',
            'supports' => array('title'),
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false,
        ));
        
        // Child Profiles
        register_post_type('srs_child', array(
            'labels' => array(
                'name' => 'Child Profiles',
                'singular_name' => 'Child Profile',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'sports-registration',
            'supports' => array('title'),
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false,
        ));
        
        // Registration Seasons/Periods
        register_post_type('srs_season', array(
            'labels' => array(
                'name' => 'Registration Seasons',
                'singular_name' => 'Registration Season',
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'sports-registration',
            'supports' => array('title'),
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => false,
            'query_var' => false,
        ));
    }
    
    /**
     * Register settings for family accounts
     */
    public function register_family_settings() {
        // Register settings section
        add_settings_section(
            'srs_family_accounts_section',
            __('Family Account Settings', 'sports-registration'),
            array($this, 'render_family_accounts_section'),
            'srs-global-settings'
        );
        
        // Enable/disable family accounts
        add_settings_field(
            'srs_family_accounts_enabled',
            __('Family Accounts', 'sports-registration'),
            array($this, 'render_family_accounts_enabled_field'),
            'srs-global-settings',
            'srs_family_accounts_section'
        );
        
        // Parent dashboard page
        add_settings_field(
            'srs_parent_dashboard_page',
            __('Parent Dashboard Page', 'sports-registration'),
            array($this, 'render_parent_dashboard_page_field'),
            'srs-global-settings',
            'srs_family_accounts_section'
        );
        
        // Login/registration page
        add_settings_field(
            'srs_family_login_page',
            __('Family Login Page', 'sports-registration'),
            array($this, 'render_family_login_page_field'),
            'srs-global-settings',
            'srs_family_accounts_section'
        );
    }
    
    /**
     * Render family accounts section description
     */
    public function render_family_accounts_section() {
        echo '<p>' . __('Configure settings for family accounts and parent registration.', 'sports-registration') . '</p>';
    }
    
    /**
     * Render family accounts enabled field
     */
    public function render_family_accounts_enabled_field() {
        $settings = get_option('srs_global_settings', array());
        $family_accounts_enabled = isset($settings['family_accounts_enabled']) ? $settings['family_accounts_enabled'] : 0;
        
        ?>
        <label for="srs_family_accounts_enabled">
            <input type="checkbox" id="srs_family_accounts_enabled" name="srs_global_settings[family_accounts_enabled]" value="1" <?php checked(1, $family_accounts_enabled); ?>>
            <?php _e('Enable Family Accounts', 'sports-registration'); ?>
        </label>
        <p class="description"><?php _e('Allow parents to create accounts, manage children, and streamline future registrations.', 'sports-registration'); ?></p>
        <?php
    }
    
    /**
     * Render parent dashboard page field
     */
    public function render_parent_dashboard_page_field() {
        $settings = get_option('srs_global_settings', array());
        $parent_dashboard_page = isset($settings['parent_dashboard_page']) ? $settings['parent_dashboard_page'] : 0;
        
        wp_dropdown_pages(array(
            'name' => 'srs_global_settings[parent_dashboard_page]',
            'echo' => 1,
            'show_option_none' => __('- Select -', 'sports-registration'),
            'option_none_value' => '0',
            'selected' => $parent_dashboard_page,
        ));
        
        echo '<p class="description">' . __('Select the page where the parent dashboard will be displayed. Add the shortcode [srs_family_dashboard] to this page.', 'sports-registration') . '</p>';
        
        if (!empty($parent_dashboard_page)) {
            echo '<a href="' . get_permalink($parent_dashboard_page) . '" target="_blank" class="button button-small">' . __('View Page', 'sports-registration') . '</a>';
        }
    }
    
    /**
     * Render family login page field
     */
    public function render_family_login_page_field() {
        $settings = get_option('srs_global_settings', array());
        $family_login_page = isset($settings['family_login_page']) ? $settings['family_login_page'] : 0;
        
        wp_dropdown_pages(array(
            'name' => 'srs_global_settings[family_login_page]',
            'echo' => 1,
            'show_option_none' => __('- Select -', 'sports-registration'),
            'option_none_value' => '0',
            'selected' => $family_login_page,
        ));
        
        echo '<p class="description">' . __('Select the page where the family login/registration form will be displayed. Add the shortcode [srs_family_login] to this page.', 'sports-registration') . '</p>';
        
        if (!empty($family_login_page)) {
            echo '<a href="' . get_permalink($family_login_page) . '" target="_blank" class="button button-small">' . __('View Page', 'sports-registration') . '</a>';
        }
    }
    
    /**
     * Register settings for registration seasons
     */
    public function register_season_settings() {
        // Register settings section
        add_settings_section(
            'srs_registration_seasons_section',
            __('Registration Seasons', 'sports-registration'),
            array($this, 'render_registration_seasons_section'),
            'srs-global-settings'
        );
        
        // Enable/disable date-based registrations
        add_settings_field(
            'srs_date_based_registrations',
            __('Date-Based Registrations', 'sports-registration'),
            array($this, 'render_date_based_registrations_field'),
            'srs-global-settings',
            'srs_registration_seasons_section'
        );
    }
    
    /**
     * Render registration seasons section description
     */
    public function render_registration_seasons_section() {
        echo '<p>' . __('Configure registration periods for different sports.', 'sports-registration') . '</p>';
    }
    
    /**
     * Render date-based registrations field
     */
    public function render_date_based_registrations_field() {
        $settings = get_option('srs_global_settings', array());
        $date_based_registrations = isset($settings['date_based_registrations']) ? $settings['date_based_registrations'] : 0;
        
        ?>
        <label for="srs_date_based_registrations">
            <input type="checkbox" id="srs_date_based_registrations" name="srs_global_settings[date_based_registrations]" value="1" <?php checked(1, $date_based_registrations); ?>>
            <?php _e('Enable Date-Based Registrations', 'sports-registration'); ?>
        </label>
        <p class="description"><?php _e('Only show registration forms that are currently active based on their start and end dates.', 'sports-registration'); ?></p>
        
        <div class="srs-season-management" style="margin-top: 15px;">
            <a href="<?php echo admin_url('edit.php?post_type=srs_season'); ?>" class="button"><?php _e('Manage Registration Seasons', 'sports-registration'); ?></a>
        </div>
        <?php
    }
    
    /**
     * Filter available registration forms based on date
     */
    public function filter_available_forms_by_date($forms) {
        $settings = get_option('srs_global_settings', array());
        $date_based_registrations = isset($settings['date_based_registrations']) ? $settings['date_based_registrations'] : 0;
        
        if (!$date_based_registrations) {
            return $forms;
        }
        
        $current_date = current_time('Y-m-d');
        $filtered_forms = array();
        
        // Get active seasons
        $args = array(
            'post_type' => 'srs_season',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => 'start_date',
                    'value' => $current_date,
                    'compare' => '<=',
                    'type' => 'DATE',
                ),
                array(
                    'key' => 'end_date',
                    'value' => $current_date,
                    'compare' => '>=',
                    'type' => 'DATE',
                ),
            ),
        );
        
        $active_seasons = get_posts($args);
        
        if (empty($active_seasons)) {
            return array(); // No active seasons
        }
        
        // Filter forms based on active seasons
        foreach ($active_seasons as $season) {
            $sport_types = get_post_meta($season->ID, 'sport_types', true);
            
            if (empty($sport_types) || !is_array($sport_types)) {
                continue;
            }
            
            foreach ($sport_types as $sport_type) {
                if (isset($forms[$sport_type])) {
                    $filtered_forms[$sport_type] = $forms[$sport_type];
                }
            }
        }
        
        return $filtered_forms;
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_script('srs-family-accounts', SRS_PLUGIN_URL . 'public/js/srs-family-accounts.js', array('jquery'), SRS_PLUGIN_VERSION, true);
        wp_enqueue_style('srs-family-accounts', SRS_PLUGIN_URL . 'public/css/srs-family-accounts.css', array(), SRS_PLUGIN_VERSION);
        
        wp_localize_script('srs-family-accounts', 'srs_family_accounts', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('srs_family_accounts_nonce'),
        ));
    }
    
    /**
     * Parent login AJAX handler
     */
    public function ajax_parent_login() {
        // Verify nonce
        check_ajax_referer('srs_family_accounts_nonce', 'nonce');
        
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            wp_send_json_error(array(
                'message' => __('Email and password are required.', 'sports-registration'),
            ));
            return;
        }
        
        // Find family account by email
        $args = array(
            'post_type' => 'srs_family',
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => 'email',
                    'value' => $email,
                    'compare' => '=',
                ),
            ),
        );
        
        $families = get_posts($args);
        
        if (empty($families)) {
            wp_send_json_error(array(
                'message' => __('No account found with that email address.', 'sports-registration'),
            ));
            return;
        }
        
        $family = $families[0];
        $stored_password = get_post_meta($family->ID, 'password', true);
        
        // Verify password
        if (!wp_check_password($password, $stored_password)) {
            wp_send_json_error(array(
                'message' => __('Invalid password. Please try again.', 'sports-registration'),
            ));
            return;
        }
        
        // Set session/cookie
        $session_token = md5(time() . $email . wp_generate_password(32, false));
        update_post_meta($family->ID, 'session_token', $session_token);
        
        setcookie('srs_family_token', $session_token, time() + (14 * DAY_IN_SECONDS), '/');
        
        // Get family data
        $family_data = $this->get_family_data($family->ID);
        
        wp_send_json_success(array(
            'message' => __('Login successful. Redirecting...', 'sports-registration'),
            'family' => $family_data,
            'redirect_url' => $this->get_dashboard_url(),
        ));
    }
    
    /**
     * Parent registration AJAX handler
     */
    public function ajax_parent_register() {
        // Verify nonce
        check_ajax_referer('srs_family_accounts_nonce', 'nonce');
        
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $address = sanitize_text_field($_POST['address'] ?? '');
        $city = sanitize_text_field($_POST['city'] ?? '');
        $state = sanitize_text_field($_POST['state'] ?? '');
        $zip = sanitize_text_field($_POST['zip'] ?? '');
        
        // Validate required fields
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($phone)) {
            wp_send_json_error(array(
                'message' => __('Please fill in all required fields.', 'sports-registration'),
            ));
            return;
        }
        
        // Check if email already exists
        $args = array(
            'post_type' => 'srs_family',
            'posts_per_page' => 1,
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
        
        // Create new family account
        $family_id = wp_insert_post(array(
            'post_title' => $first_name . ' ' . $last_name . ' Family',
            'post_type' => 'srs_family',
            'post_status' => 'publish',
        ));
        
        if (is_wp_error($family_id)) {
            wp_send_json_error(array(
                'message' => __('Failed to create family account. Please try again.', 'sports-registration'),
            ));
            return;
        }
        
        // Save family data
        update_post_meta($family_id, 'first_name', $first_name);
        update_post_meta($family_id, 'last_name', $last_name);
        update_post_meta($family_id, 'email', $email);
        update_post_meta($family_id, 'password', wp_hash_password($password));
        update_post_meta($family_id, 'phone', $phone);
        update_post_meta($family_id, 'address', $address);
        update_post_meta($family_id, 'city', $city);
        update_post_meta($family_id, 'state', $state);
        update_post_meta($family_id, 'zip', $zip);
        
        // Set session/cookie
        $session_token = md5(time() . $email . wp_generate_password(32, false));
        update_post_meta($family_id, 'session_token', $session_token);
        
        setcookie('srs_family_token', $session_token, time() + (14 * DAY_IN_SECONDS), '/');
        
        // Get family data
        $family_data = $this->get_family_data($family_id);
        
        wp_send_json_success(array(
            'message' => __('Account created successfully. Redirecting...', 'sports-registration'),
            'family' => $family_data,
            'redirect_url' => $this->get_dashboard_url(),
        ));
    }
    
    /**
     * Parent logout AJAX handler
     */
    public function ajax_parent_logout() {
        // Verify nonce
        check_ajax_referer('srs_family_accounts_nonce', 'nonce');
        
        // Clear cookie
        setcookie('srs_family_token', '', time() - 3600, '/');
        
        wp_send_json_success(array(
            'message' => __('Logged out successfully.', 'sports-registration'),
            'redirect_url' => $this->get_login_url(),
        ));
    }
    
    /**
     * Add child AJAX handler
     */
    public function ajax_add_child() {
        // Verify nonce
        check_ajax_referer('srs_family_accounts_nonce', 'nonce');
        
        // Get family ID from session
        $family_id = $this->get_current_family_id();
        
        if (!$family_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to add a child.', 'sports-registration'),
            ));
            return;
        }
        
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $gender = sanitize_text_field($_POST['gender'] ?? '');
        $dob = sanitize_text_field($_POST['dob'] ?? '');
        $shirt_size = sanitize_text_field($_POST['shirt_size'] ?? '');
        $school = sanitize_text_field($_POST['school'] ?? '');
        $medical_issues = sanitize_textarea_field($_POST['medical_issues'] ?? '');
        $medical_insurance = sanitize_text_field($_POST['medical_insurance'] ?? '');
        
        // Validate required fields
        if (empty($first_name) || empty($last_name) || empty($gender) || empty($dob)) {
            wp_send_json_error(array(
                'message' => __('Please fill in all required fields.', 'sports-registration'),
            ));
            return;
        }
        
        // Create new child profile
        $child_id = wp_insert_post(array(
            'post_title' => $first_name . ' ' . $last_name,
            'post_type' => 'srs_child',
            'post_status' => 'publish',
        ));
        
        if (is_wp_error($child_id)) {
            wp_send_json_error(array(
                'message' => __('Failed to create child profile. Please try again.', 'sports-registration'),
            ));
            return;
        }
        
        // Save child data
        update_post_meta($child_id, 'family_id', $family_id);
        update_post_meta($child_id, 'first_name', $first_name);
        update_post_meta($child_id, 'last_name', $last_name);
        update_post_meta($child_id, 'gender', $gender);
        update_post_meta($child_id, 'dob', $dob);
        update_post_meta($child_id, 'shirt_size', $shirt_size);
        update_post_meta($child_id, 'school', $school);
        update_post_meta($child_id, 'medical_issues', $medical_issues);
        update_post_meta($child_id, 'medical_insurance', $medical_insurance);
        
        // Add child to family
        $children = get_post_meta($family_id, 'children', true);
        
        if (empty($children) || !is_array($children)) {
            $children = array();
        }
        
        $children[] = $child_id;
        update_post_meta($family_id, 'children', $children);
        
        // Get updated family data
        $family_data = $this->get_family_data($family_id);
        
        wp_send_json_success(array(
            'message' => __('Child added successfully.', 'sports-registration'),
            'family' => $family_data,
            'child_id' => $child_id,
        ));
    }
    
    /**
     * Update child AJAX handler
     */
    public function ajax_update_child() {
        // Verify nonce
        check_ajax_referer('srs_family_accounts_nonce', 'nonce');
        
        // Get family ID from session
        $family_id = $this->get_current_family_id();
        
        if (!$family_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to update a child.', 'sports-registration'),
            ));
            return;
        }
        
        $child_id = intval($_POST['child_id'] ?? 0);
        
        // Verify child belongs to family
        $children = get_post_meta($family_id, 'children', true);
        
        if (empty($children) || !is_array($children) || !in_array($child_id, $children)) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to update this child.', 'sports-registration'),
            ));
            return;
        }
        
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $gender = sanitize_text_field($_POST['gender'] ?? '');
        $dob = sanitize_text_field($_POST['dob'] ?? '');
        $shirt_size = sanitize_text_field($_POST['shirt_size'] ?? '');
        $school = sanitize_text_field($_POST['school'] ?? '');
        $medical_issues = sanitize_textarea_field($_POST['medical_issues'] ?? '');
        $medical_insurance = sanitize_text_field($_POST['medical_insurance'] ?? '');
        
        // Validate required fields
        if (empty($first_name) || empty($last_name) || empty($gender) || empty($dob)) {
            wp_send_json_error(array(
                'message' => __('Please fill in all required fields.', 'sports-registration'),
            ));
            return;
        }
        
        // Update child data
        wp_update_post(array(
            'ID' => $child_id,
            'post_title' => $first_name . ' ' . $last_name,
        ));
        
        update_post_meta($child_id, 'first_name', $first_name);
        update_post_meta($child_id, 'last_name', $last_name);
        update_post_meta($child_id, 'gender', $gender);
        update_post_meta($child_id, 'dob', $dob);
        update_post_meta($child_id, 'shirt_size', $shirt_size);
        update_post_meta($child_id, 'school', $school);
        update_post_meta($child_id, 'medical_issues', $medical_issues);
        update_post_meta($child_id, 'medical_insurance', $medical_insurance);
        
        // Get updated family data
        $family_data = $this->get_family_data($family_id);
        
        wp_send_json_success(array(
            'message' => __('Child updated successfully.', 'sports-registration'),
            'family' => $family_data,
        ));
    }
    
    /**
     * Remove child AJAX handler
     */
    public function ajax_remove_child() {
        // Verify nonce
        check_ajax_referer('srs_family_accounts_nonce', 'nonce');
        
        // Get family ID from session
        $family_id = $this->get_current_family_id();
        
        if (!$family_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to remove a child.', 'sports-registration'),
            ));
            return;
        }
        
        $child_id = intval($_POST['child_id'] ?? 0);
        
        // Verify child belongs to family
        $children = get_post_meta($family_id, 'children', true);
        
        if (empty($children) || !is_array($children) || !in_array($child_id, $children)) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to remove this child.', 'sports-registration'),
            ));
            return;
        }
        
        // Remove child from family
        $children = array_diff($children, array($child_id));
        update_post_meta($family_id, 'children', $children);
        
        // Delete child profile
        wp_delete_post($child_id, true);
        
        // Get updated family data
        $family_data = $this->get_family_data($family_id);
        
        wp_send_json_success(array(
            'message' => __('Child removed successfully.', 'sports-registration'),
            'family' => $family_data,
        ));
    }
    
    /**
     * Get current family ID from session
     */
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
    
    /**
     * Get current family data
     */
    public function get_current_family_data() {
        $family_id = $this->get_current_family_id();
        
        if (!$family_id) {
            return false;
        }
        
        return $this->get_family_data($family_id);
    }
    
    /**
     * Get family data by ID
     */
    public function get_family_data($family_id) {
        $family = get_post($family_id);
        
        if (!$family || $family->post_type !== 'srs_family') {
            return false;
        }
        
        $data = array(
            'id' => $family->ID,
            'first_name' => get_post_meta($family->ID, 'first_name', true),
            'last_name' => get_post_meta($family->ID, 'last_name', true),
            'email' => get_post_meta($family->ID, 'email', true),
            'phone' => get_post_meta($family->ID, 'phone', true),
            'address' => get_post_meta($family->ID, 'address', true),
            'city' => get_post_meta($family->ID, 'city', true),
            'state' => get_post_meta($family->ID, 'state', true),
            'zip' => get_post_meta($family->ID, 'zip', true),
            'children' => array(),
        );
        
        // Get children
        $children_ids = get_post_meta($family->ID, 'children', true);
        
        if (!empty($children_ids) && is_array($children_ids)) {
            foreach ($children_ids as $child_id) {
                $child_data = $this->get_child_data($child_id);
                
                if ($child_data) {
                    $data['children'][] = $child_data;
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Get child data by ID
     */
    public function get_child_data($child_id) {
        $child = get_post($child_id);
        
        if (!$child || $child->post_type !== 'srs_child') {
            return false;
        }
        
        return array(
            'id' => $child->ID,
            'first_name' => get_post_meta($child->ID, 'first_name', true),
            'last_name' => get_post_meta($child->ID, 'last_name', true),
            'gender' => get_post_meta($child->ID, 'gender', true),
            'dob' => get_post_meta($child->ID, 'dob', true),
            'shirt_size' => get_post_meta($child->ID, 'shirt_size', true),
            'school' => get_post_meta($child->ID, 'school', true),
            'medical_issues' => get_post_meta($child->ID, 'medical_issues', true),
            'medical_insurance' => get_post_meta($child->ID, 'medical_insurance', true),
            'registrations' => $this->get_child_registrations($child->ID),
        );
    }
    
    /**
     * Get child registrations
     */
    public function get_child_registrations($child_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'srs_registrations';
        
        $registrations = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE form_data LIKE %s ORDER BY created_at DESC",
                '%"child_id":"' . $child_id . '"%'
            )
        );
        
        $data = array();
        
        foreach ($registrations as $registration) {
            $form_data = json_decode($registration->form_data, true);
            
            $data[] = array(
                'id' => $registration->id,
                'form_type' => $registration->form_type,
                'created_at' => $registration->created_at,
                'payment_status' => $registration->payment_status,
                'payment_amount' => $registration->payment_amount,
            );
        }
        
        return $data;
    }
    
    /**
     * Get dashboard URL
     */
    public function get_dashboard_url() {
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
    public function get_login_url() {
        $settings = get_option('srs_global_settings', array());
        $login_page = isset($settings['family_login_page']) ? $settings['family_login_page'] : 0;
        
        if (empty($login_page)) {
            return home_url();
        }
        
        return get_permalink($login_page);
    }
    
    /**
     * Render family dashboard
     */
    public function render_family_dashboard($atts) {
        $family_data = $this->get_current_family_data();
        
        if (!$family_data) {
            // Not logged in, redirect to login page
            wp_redirect($this->get_login_url());
            exit;
        }
        
        ob_start();
        include SRS_PLUGIN_DIR . 'public/partials/srs-family-dashboard.php';
        return ob_get_clean();
    }
    
    /**
     * Render family login form
     */
    public function render_family_login_form($atts) {
        $family_id = $this->get_current_family_id();
        
        if ($family_id) {
            // Already logged in, redirect to dashboard
            wp_redirect($this->get_dashboard_url());
            exit;
        }
        
        ob_start();
        include SRS_PLUGIN_DIR . 'public/partials/srs-family-login.php';
        return ob_get_clean();
    }
}

// Initialize family accounts system
new SRS_Family_Accounts();

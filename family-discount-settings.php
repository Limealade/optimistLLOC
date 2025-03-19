<?php
/**
 * Add pricing and discount settings to the global settings page
 */
class SRS_Pricing {
    public function __construct() {
        add_action('admin_init', array($this, 'register_pricing_settings'));
        add_filter('srs_calculate_registration_fee', array($this, 'calculate_family_discount'), 10, 2);
    }
    
    /**
     * Register pricing and discount settings
     */
    public function register_pricing_settings() {
        // Register settings section
        add_settings_section(
            'srs_pricing_section',
            __('Pricing & Family Discounts', 'sports-registration'),
            array($this, 'render_pricing_section_description'),
            'srs-global-settings'
        );
        
        // Base price settings for each sport
        add_settings_field(
            'srs_base_pricing',
            __('Base Registration Fees', 'sports-registration'),
            array($this, 'render_base_pricing_fields'),
            'srs-global-settings',
            'srs_pricing_section'
        );
        
        // Family discount settings
        add_settings_field(
            'srs_family_discount',
            __('Family Discounts', 'sports-registration'),
            array($this, 'render_family_discount_fields'),
            'srs-global-settings',
            'srs_pricing_section'
        );
    }
    
    /**
     * Render pricing section description
     */
    public function render_pricing_section_description() {
        echo '<p>' . __('Configure registration fees and family discounts.', 'sports-registration') . '</p>';
    }
    
    /**
     * Render base pricing fields
     */
    public function render_base_pricing_fields() {
        $settings = get_option('srs_global_settings', array());
        $base_pricing = isset($settings['base_pricing']) ? $settings['base_pricing'] : array(
            'basketball' => '50',
            'soccer' => '50',
            'cheerleading' => '60',
            'volleyball' => '50',
        );
        
        ?>
        <div class="srs-base-pricing-fields">
            <div class="srs-field-group">
                <label for="srs_base_pricing_basketball">
                    <?php _e('Basketball Fee ($)', 'sports-registration'); ?>
                </label>
                <input type="number" id="srs_base_pricing_basketball" 
                       name="srs_global_settings[base_pricing][basketball]" 
                       value="<?php echo esc_attr($base_pricing['basketball']); ?>" 
                       min="0" step="0.01" class="small-text">
            </div>
            
            <div class="srs-field-group">
                <label for="srs_base_pricing_soccer">
                    <?php _e('Soccer Fee ($)', 'sports-registration'); ?>
                </label>
                <input type="number" id="srs_base_pricing_soccer" 
                       name="srs_global_settings[base_pricing][soccer]" 
                       value="<?php echo esc_attr($base_pricing['soccer']); ?>" 
                       min="0" step="0.01" class="small-text">
            </div>
            
            <div class="srs-field-group">
                <label for="srs_base_pricing_cheerleading">
                    <?php _e('Cheerleading Fee ($)', 'sports-registration'); ?>
                </label>
                <input type="number" id="srs_base_pricing_cheerleading" 
                       name="srs_global_settings[base_pricing][cheerleading]" 
                       value="<?php echo esc_attr($base_pricing['cheerleading']); ?>" 
                       min="0" step="0.01" class="small-text">
            </div>
            
            <div class="srs-field-group">
                <label for="srs_base_pricing_volleyball">
                    <?php _e('Volleyball Fee ($)', 'sports-registration'); ?>
                </label>
                <input type="number" id="srs_base_pricing_volleyball" 
                       name="srs_global_settings[base_pricing][volleyball]" 
                       value="<?php echo esc_attr($base_pricing['volleyball']); ?>" 
                       min="0" step="0.01" class="small-text">
            </div>
            
            <p class="srs-field-description">
                <?php _e('Set the standard registration fee for each sport. These fees will be used as the starting point before any family discounts are applied.', 'sports-registration'); ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Render family discount fields
     */
    public function render_family_discount_fields() {
        $settings = get_option('srs_global_settings', array());
        $family_discount = isset($settings['family_discount']) ? $settings['family_discount'] : array(
            'enabled' => 1,
            'second_child' => '10',
            'third_child' => '15',
            'additional_child' => '20',
        );
        
        ?>
        <div class="srs-family-discount-fields">
            <div class="srs-field-group">
                <label for="srs_family_discount_enabled">
                    <input type="checkbox" id="srs_family_discount_enabled" 
                           name="srs_global_settings[family_discount][enabled]" 
                           value="1" <?php checked(1, $family_discount['enabled']); ?>>
                    <?php _e('Enable Family Discounts', 'sports-registration'); ?>
                </label>
                <p class="srs-field-description">
                    <?php _e('If enabled, families registering multiple children will receive discounts.', 'sports-registration'); ?>
                </p>
            </div>
            
            <div class="srs-discount-tiers" style="margin-top: 15px;">
                <div class="srs-field-group">
                    <label for="srs_family_discount_second_child">
                        <?php _e('Second Child Discount (%)', 'sports-registration'); ?>
                    </label>
                    <input type="number" id="srs_family_discount_second_child" 
                           name="srs_global_settings[family_discount][second_child]" 
                           value="<?php echo esc_attr($family_discount['second_child']); ?>" 
                           min="0" max="100" class="small-text">
                    <p class="srs-field-description">
                        <?php _e('Discount percentage for the second child in a family.', 'sports-registration'); ?>
                    </p>
                </div>
                
                <div class="srs-field-group">
                    <label for="srs_family_discount_third_child">
                        <?php _e('Third Child Discount (%)', 'sports-registration'); ?>
                    </label>
                    <input type="number" id="srs_family_discount_third_child" 
                           name="srs_global_settings[family_discount][third_child]" 
                           value="<?php echo esc_attr($family_discount['third_child']); ?>" 
                           min="0" max="100" class="small-text">
                    <p class="srs-field-description">
                        <?php _e('Discount percentage for the third child in a family.', 'sports-registration'); ?>
                    </p>
                </div>
                
                <div class="srs-field-group">
                    <label for="srs_family_discount_additional_child">
                        <?php _e('Additional Children Discount (%)', 'sports-registration'); ?>
                    </label>
                    <input type="number" id="srs_family_discount_additional_child" 
                           name="srs_global_settings[family_discount][additional_child]" 
                           value="<?php echo esc_attr($family_discount['additional_child']); ?>" 
                           min="0" max="100" class="small-text">
                    <p class="srs-field-description">
                        <?php _e('Discount percentage for the fourth child and beyond in a family.', 'sports-registration'); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Calculate family discount based on number of children enrolled
     * 
     * @param float $fee The original registration fee
     * @param array $form_data The form data including family information
     * @return float The discounted fee
     */
    public function calculate_family_discount($fee, $form_data) {
        $settings = get_option('srs_global_settings', array());
        
        // Check if family discounts are enabled
        if (empty($settings['family_discount']['enabled'])) {
            return $fee;
        }
        
        // Get the number of children already registered from this family
        $family_count = $this->get_family_registration_count($form_data);
        
        // First child - no discount
        if ($family_count === 0) {
            return $fee;
        }
        
        // Apply discount based on which child this is
        $discount_percentage = 0;
        
        if ($family_count === 1) {
            // This is the second child
            $discount_percentage = floatval($settings['family_discount']['second_child']);
        } elseif ($family_count === 2) {
            // This is the third child
            $discount_percentage = floatval($settings['family_discount']['third_child']);
        } else {
            // This is the fourth or more child
            $discount_percentage = floatval($settings['family_discount']['additional_child']);
        }
        
        // Calculate discounted fee
        $discount_amount = $fee * ($discount_percentage / 100);
        $discounted_fee = $fee - $discount_amount;
        
        return round($discounted_fee, 2);
    }
    
    /**
     * Get the number of children already registered from the same family
     * 
     * @param array $form_data The form data including family information
     * @return int Number of existing registrations from the same family
     */
    private function get_family_registration_count($form_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'srs_registrations';
        
        // We'll identify families by last name and address (basic approach)
        $last_name = sanitize_text_field($form_data['last_name'] ?? '');
        $address = sanitize_text_field($form_data['address'] ?? '');
        $zip = sanitize_text_field($form_data['zip'] ?? '');
        
        if (empty($last_name) || empty($address) || empty($zip)) {
            return 0;
        }
        
        // Count registrations with the same last name and address
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
            WHERE last_name = %s 
            AND form_data LIKE %s
            AND form_data LIKE %s
            AND created_at >= %s",
            $last_name,
            '%' . $wpdb->esc_like('"address":"' . $address) . '%',
            '%' . $wpdb->esc_like('"zip":"' . $zip) . '%',
            date('Y-01-01') // Current year only
        );
        
        $count = $wpdb->get_var($query);
        
        return intval($count);
    }
}

// Initialize the pricing class
new SRS_Pricing();

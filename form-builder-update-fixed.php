<?php
/**
 * Updates to the SRS_Forms class to incorporate the family discount system
 */

/**
 * This class extends the SRS_Forms class with updated methods for family discount
 */
class SRS_Forms_Extended extends SRS_Forms {
    
    /**
     * Modified render_form method to handle dynamic pricing
     * Override the parent class method
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
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Enqueue the appropriate scripts for the form
     */
    public function enqueue_scripts() {
        parent::enqueue_scripts();
        
        // Enqueue the family discount calculation script
        wp_enqueue_script(
            'srs-family-discount', 
            SRS_PLUGIN_URL . 'public/js/srs-family-discount.js',
            array('jquery'),
            SRS_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script('srs-family-discount', 'srs_discount_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('srs_family_discount_nonce'),
        ));
    }
}

// Initialize the extended class
$forms_extended = new SRS_Forms_Extended();
add_action('init', array($forms_extended, 'enqueue_scripts'));

/**
 * Create the separate JavaScript file for family discount calculations
 */
function srs_create_family_discount_js() {
    $js_dir = SRS_PLUGIN_DIR . 'public/js/';
    
    // Create directory if it doesn't exist
    if (!file_exists($js_dir)) {
        wp_mkdir_p($js_dir);
    }
    
    $js_file = $js_dir . 'srs-family-discount.js';
    
    if (!file_exists($js_file)) {
        $js_content = <<<EOT
/**
 * Family Discount Calculator
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Family discount calculator
        function initializeFamilyDiscount() {
            $('.srs-registration-form').each(function() {
                var form = $(this);
                var addressField = form.find('input[name="address"]');
                var lastNameField = form.find('input[name="last_name"]');
                var zipField = form.find('input[name="zip"]');
                
                function checkFamilyDiscount() {
                    var lastName = lastNameField.val().trim();
                    var address = addressField.val().trim();
                    var zip = zipField.val().trim();
                    var formType = form.find('input[name="form_type"]').val();
                    
                    if (lastName && address && zip) {
                        // Send AJAX request to check for existing family registrations
                        $.ajax({
                            url: srs_discount_params.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'srs_check_family_discount',
                                security: srs_discount_params.nonce,
                                last_name: lastName,
                                address: address,
                                zip: zip,
                                form_type: formType
                            },
                            success: function(response) {
                                if (response.success) {
                                    updatePriceDisplay(response.data);
                                }
                            }
                        });
                    }
                }
                
                function updatePriceDisplay(data) {
                    var basePrice = parseFloat(data.base_price);
                    var discountedPrice = parseFloat(data.discounted_price);
                    var discountAmount = basePrice - discountedPrice;
                    var discountPercent = data.discount_percent;
                    var familyCount = data.family_count;
                    
                    var feeBaseElement = form.find('.srs-fee-base .srs-fee-amount');
                    var feeDiscountElement = form.find('.srs-fee-discount');
                    var feeDiscountAmountElement = form.find('.srs-fee-discount .srs-fee-amount');
                    var feeTotalElement = form.find('.srs-fee-total .srs-fee-amount');
                    var paymentAmountField = form.find('input[name="payment_amount"]');
                    
                    if (feeBaseElement.length) {
                        feeBaseElement.text('$' + basePrice.toFixed(2));
                    }
                    
                    if (discountAmount > 0 && feeDiscountElement.length && feeDiscountAmountElement.length) {
                        // Update the discount label based on which child this is
                        var childNumber = familyCount + 1;
                        var childLabel = '';
                        
                        switch (childNumber) {
                            case 2:
                                childLabel = 'Second Child';
                                break;
                            case 3:
                                childLabel = 'Third Child';
                                break;
                            default:
                                childLabel = 'Additional Child';
                                break;
                        }
                        
                        feeDiscountElement.find('.srs-fee-label').text(
                            childLabel + ' Discount (' + discountPercent + '%):');
                        feeDiscountAmountElement.text('-$' + discountAmount.toFixed(2));
                        feeDiscountElement.show();
                    } else if (feeDiscountElement.length) {
                        feeDiscountElement.hide();
                    }
                    
                    if (feeTotalElement.length) {
                        feeTotalElement.text('$' + discountedPrice.toFixed(2));
                    }
                    
                    if (paymentAmountField.length) {
                        paymentAmountField.val(discountedPrice.toFixed(2));
                    }
                }
                
                // Check for family discount when key fields change
                if (lastNameField.length) {
                    lastNameField.on('blur', checkFamilyDiscount);
                }
                
                if (addressField.length) {
                    addressField.on('blur', checkFamilyDiscount);
                }
                
                if (zipField.length) {
                    zipField.on('blur', checkFamilyDiscount);
                }
            });
        }
        
        initializeFamilyDiscount();
    });
})(jQuery);
EOT;

        file_put_contents($js_file, $js_content);
    }
}

add_action('plugins_loaded', 'srs_create_family_discount_js');

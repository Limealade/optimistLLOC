<?php
/**
 * Updates to the SRS_Forms class to incorporate the family discount system
 */

/**
 * Modify the render_form method to handle dynamic pricing
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
    
    <script>
        // Form handling script
        document.addEventListener('DOMContentLoaded', function() {
            var form = document.getElementById('srs-registration-form-<?php echo esc_js($form_type); ?>');
            var messages = form.parentNode.querySelector('.srs-form-messages');
            
            // Date of birth age calculation
            var dobField = form.querySelector('input[name="dob"]');
            if (dobField) {
                dobField.addEventListener('change', function() {
                    var dob = new Date(this.value);
                    if (!isNaN(dob.getTime())) {
                        var today = new Date();
                        var referenceDate = new Date(today.getFullYear() - 1, 7, 1); // August 1 of last year
                        
                        var age = referenceDate.getFullYear() - dob.getFullYear();
                        
                        // Adjust age if birthday hasn't occurred yet in the reference year
                        var dobMonth = dob.getMonth();
                        var refMonth = referenceDate.getMonth();
                        
                        if (refMonth < dobMonth || (refMonth === dobMonth && referenceDate.getDate() < dob.getDate())) {
                            age--;
                        }
                        
                        // Show popup with age
                        var agePopup = document.createElement('div');
                        agePopup.className = 'srs-age-popup';
                        agePopup.textContent = 'Age as of August 1, ' + referenceDate.getFullYear() + ': ' + age + ' years';
                        
                        // Remove any existing popup
                        var existingPopup = this.parentNode.querySelector('.srs-age-popup');
                        if (existingPopup) {
                            existingPopup.remove();
                        }
                        
                        this.parentNode.appendChild(agePopup);
                        
                        // Auto-remove popup after 5 seconds
                        setTimeout(function() {
                            agePopup.remove();
                        }, 5000);
                    }
                });
            }
            
            // Family discount calculator
            var addressField = form.querySelector('input[name="address"]');
            var lastNameField = form.querySelector('input[name="last_name"]');
            var zipField = form.querySelector('input[name="zip"]');
            
            function checkFamilyDiscount() {
                var lastName = lastNameField ? lastNameField.value.trim() : '';
                var address = addressField ? addressField.value.trim() : '';
                var zip = zipField ? zipField.value.trim() : '';
                
                if (lastName && address && zip) {
                    // Send AJAX request to check for existing family registrations
                    var formData = new FormData();
                    formData.append('action', 'srs_check_family_discount');
                    formData.append('last_name', lastName);
                    formData.append('address', address);
                    formData.append('zip', zip);
                    formData.append('form_type', '<?php echo esc_js($form_type); ?>');
                    formData.append('security', '<?php echo wp_create_nonce('srs_family_discount_nonce'); ?>');
                    
                    fetch(srs_vars.ajax_url, {
                        method: 'POST',
                        body: formData,
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(data) {
                        if (data.success) {
                            updatePriceDisplay(data.data);
                        }
                    })
                    .catch(function(error) {
                        console.error('Error checking family discount:', error);
                    });
                }
            }
            
            function updatePriceDisplay(data) {
                var basePrice = parseFloat(data.base_price);
                var discountedPrice = parseFloat(data.discounted_price);
                var discountAmount = basePrice - discountedPrice;
                var discountPercent = data.discount_percent;
                var familyCount = data.family_count;
                
                var feeBaseElement = form.querySelector('.srs-fee-base .srs-fee-amount');
                var feeDiscountElement = form.querySelector('.srs-fee-discount');
                var feeDiscountAmountElement = form.querySelector('.srs-fee-discount .srs-fee-amount');
                var feeTotalElement = form.querySelector('.srs-fee-total .srs-fee-amount');
                var paymentAmountField = form.querySelector('input[name="payment_amount"]');
                
                if (feeBaseElement) {
                    feeBaseElement.textContent = '$' + basePrice.toFixed(2);
                }
                
                if (discountAmount > 0 && feeDiscountElement && feeDiscountAmountElement) {
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
                    
                    feeDiscountElement.querySelector('.srs-fee-label').textContent = 
                        childLabel + ' Discount (' + discountPercent + '%):';
                    feeDiscountAmountElement.textContent = '-$' + discountAmount.toFixed(2);
                    feeDiscountElement.style.display = 'flex';
                } else if (feeDiscountElement) {
                    feeDiscountElement.style.display = 'none';
                }
                
                if (feeTotalElement) {
                    feeTotalElement.textContent = '$' + discountedPrice.toFixed(2);
                }
                
                if (paymentAmountField) {
                    paymentAmountField.value = discountedPrice.toFixed(2);
                }
            }
            
            // Check for family discount when key fields change
            if (lastNameField) {
                lastNameField.addEventListener('blur', checkFamilyDiscount);
            }
            
            if (addressField) {
                addressField.addEventListener('blur', checkFamilyDiscount);
            }
            
            if (zipField) {
                zipField.addEventListener('blur', checkFamilyDiscount);
            }
            
            // Handle form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validate form
                var isValid = true;
                var requiredFields = form.querySelectorAll('[required]');
                requiredFields.forEach(function(field) {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('srs-error');
                    } else {
                        field.classList.remove('srs-error');
                    }
                });
                
                if (!isValid) {
                    messages.style.display = 'block';
                    messages.innerHTML = '<div class="srs-error-message">Please fill in all required fields.</div>';
                    messages.scrollIntoView({behavior: 'smooth'});
                    return;
                }
                
                // Handle payment if needed
                var paymentAmount = form.querySelector('input[name="payment_amount"]');
                if (paymentAmount && parseFloat(paymentAmount.value) > 0) {
                    var paymentMethod = form.querySelector('input[name="payment_method"]:checked');
                    if (paymentMethod) {
                        if (paymentMethod.value === 'square') {
                            // Process Square payment
                            // This would be handled by the Square SDK
                        } else if (paymentMethod.value === 'paypal') {
                            // Process PayPal payment
                            // This would be handled by the PayPal SDK
                        }
                    }
                } else {
                    // Submit form data via AJAX
                    submitFormData(form);
                }
            });
            
            // Payment method toggle
            var paymentMethods = form.querySelectorAll('input[name="payment_method"]');
            paymentMethods.forEach(function(method) {
                method.addEventListener('change', function() {
                    var paymentForms = form.querySelectorAll('.srs-payment-form');
                    paymentForms.forEach(function(paymentForm) {
                        paymentForm.style.display = 'none';
                    });
                    
                    var selectedForm = form.querySelector('#' + this.value + '-payment-form');
                    if (selectedForm) {
                        selectedForm.style.display = 'block';
                    }
                });
            });
            
            function submitFormData(form) {
                var formData = new FormData(form);
                formData.append('action', 'srs_submit_registration');
                formData.append('security', '<?php echo wp_create_nonce('srs_form_nonce'); ?>');
                
                // Show loading state
                form.classList.add('srs-loading');
                var submitBtn = form.querySelector('.srs-submit-button');
                var originalBtnText = submitBtn.textContent;
                submitBtn.textContent = 'Submitting...';
                submitBtn.disabled = true;
                
                fetch(srs_vars.ajax_url, {
                    method: 'POST',
                    body: formData,
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    form.classList.remove('srs-loading');
                    submitBtn.textContent = originalBtnText;
                    submitBtn.disabled = false;
                    
                    messages.style.display = 'block';
                    
                    if (data.success) {
                        messages.innerHTML = '<div class="srs-success-message">' + data.data.message + '</div>';
                        form.reset();
                    } else {
                        messages.innerHTML = '<div class="srs-error-message">' + data.data.message + '</div>';
                    }
                    
                    messages.scrollIntoView({behavior: 'smooth'});
                })
                .catch(function(error) {
                    form.classList.remove('srs-loading');
                    submitBtn.textContent = originalBtnText;
                    submitBtn.disabled = false;
                    
                    messages.style.display = 'block';
                    messages.innerHTML = '<div class="srs-error-message">An error occurred. Please try again.</div>';
                    messages.scrollIntoView({behavior: 'smooth'});
                    
                    console.error('Error:', error);
                });
            }
        });
    </script>
    <?php
    return ob_get_clean();
}

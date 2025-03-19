<?php
/**
 * Family Dashboard Template
 * 
 * Displays the parent dashboard with family information, children profiles, and registration options
 */
?>
<div class="srs-family-dashboard-container">
    <div class="srs-family-header">
        <h2><?php echo esc_html($family_data['first_name'] . ' ' . $family_data['last_name']); ?> Family Dashboard</h2>
        <div class="srs-family-actions">
            <button id="srs-edit-family-profile" class="srs-button">Edit Family Profile</button>
            <button id="srs-logout" class="srs-button srs-button-outline">Log Out</button>
        </div>
    </div>
    
    <div class="srs-dashboard-content">
        <!-- Family Information -->
        <div class="srs-dashboard-section">
            <div class="srs-section-header">
                <h3>Family Information</h3>
            </div>
            
            <div class="srs-family-info">
                <div class="srs-info-row">
                    <span class="srs-info-label">Parents:</span>
                    <span class="srs-info-value"><?php echo esc_html($family_data['first_name'] . ' ' . $family_data['last_name']); ?></span>
                </div>
                
                <div class="srs-info-row">
                    <span class="srs-info-label">Email:</span>
                    <span class="srs-info-value"><?php echo esc_html($family_data['email']); ?></span>
                </div>
                
                <div class="srs-info-row">
                    <span class="srs-info-label">Phone:</span>
                    <span class="srs-info-value"><?php echo esc_html($family_data['phone']); ?></span>
                </div>
                
                <div class="srs-info-row">
                    <span class="srs-info-label">Address:</span>
                    <span class="srs-info-value">
                        <?php echo esc_html($family_data['address']); ?><br>
                        <?php echo esc_html($family_data['city'] . ', ' . $family_data['state'] . ' ' . $family_data['zip']); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Children Profiles -->
        <div class="srs-dashboard-section">
            <div class="srs-section-header">
                <h3>Children</h3>
                <button id="srs-add-child" class="srs-button srs-button-small">Add Child</button>
            </div>
            
            <div class="srs-children-list">
                <?php if (empty($family_data['children'])): ?>
                    <div class="srs-empty-state">
                        <p>No children added yet. Click "Add Child" to get started.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($family_data['children'] as $child): ?>
                        <div class="srs-child-card" data-child-id="<?php echo esc_attr($child['id']); ?>">
                            <div class="srs-child-header">
                                <h4><?php echo esc_html($child['first_name'] . ' ' . $child['last_name']); ?></h4>
                                <div class="srs-child-actions">
                                    <button class="srs-edit-child srs-button srs-button-small">Edit</button>
                                    <button class="srs-remove-child srs-button srs-button-small srs-button-danger">Remove</button>
                                </div>
                            </div>
                            
                            <div class="srs-child-info">
                                <div class="srs-info-row">
                                    <span class="srs-info-label">Gender:</span>
                                    <span class="srs-info-value"><?php echo esc_html(ucfirst($child['gender'])); ?></span>
                                </div>
                                
                                <div class="srs-info-row">
                                    <span class="srs-info-label">Date of Birth:</span>
                                    <span class="srs-info-value"><?php echo esc_html(date('F j, Y', strtotime($child['dob']))); ?></span>
                                </div>
                                
                                <?php if (!empty($child['shirt_size'])): ?>
                                    <div class="srs-info-row">
                                        <span class="srs-info-label">Shirt Size:</span>
                                        <span class="srs-info-value"><?php echo esc_html($child['shirt_size']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($child['school'])): ?>
                                    <div class="srs-info-row">
                                        <span class="srs-info-label">School:</span>
                                        <span class="srs-info-value"><?php echo esc_html($child['school']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($child['registrations'])): ?>
                                <div class="srs-child-registrations">
                                    <h5>Registration History</h5>
                                    <ul class="srs-registration-list">
                                        <?php foreach ($child['registrations'] as $registration): ?>
                                            <li class="srs-registration-item">
                                                <span class="srs-registration-type"><?php echo esc_html(ucfirst($registration['form_type'])); ?></span>
                                                <span class="srs-registration-date"><?php echo esc_html(date('m/d/Y', strtotime($registration['created_at']))); ?></span>
                                                <span class="srs-registration-status srs-status-<?php echo esc_attr($registration['payment_status']); ?>">
                                                    <?php echo esc_html(ucfirst($registration['payment_status'])); ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Available Registrations -->
        <div class="srs-dashboard-section">
            <div class="srs-section-header">
                <h3>Available Registrations</h3>
            </div>
            
            <?php
            // Get available registration forms
            $forms_obj = new SRS_Forms();
            $available_forms = $forms_obj->get_available_forms();
            
            if (empty($available_forms)): ?>
                <div class="srs-empty-state">
                    <p>No registration options are currently available.</p>
                </div>
            <?php else: ?>
                <div class="srs-registration-options">
                    <?php foreach ($available_forms as $form_type => $form_settings): ?>
                        <div class="srs-registration-option" data-form-type="<?php echo esc_attr($form_type); ?>">
                            <div class="srs-option-header">
                                <h4><?php echo esc_html($form_settings['title']); ?></h4>
                                <?php if (isset($form_settings['season'])): ?>
                                    <span class="srs-season-label"><?php echo esc_html($form_settings['season']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (isset($form_settings['description'])): ?>
                                <div class="srs-option-description">
                                    <?php echo wp_kses_post($form_settings['description']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($family_data['children'])): ?>
                                <div class="srs-register-children">
                                    <button class="srs-register-children-btn srs-button" data-form-type="<?php echo esc_attr($form_type); ?>">
                                        Register Children
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="srs-register-children">
                                    <p>Add children to your family profile to register.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modals -->
<div id="srs-modal-backdrop" class="srs-modal-backdrop" style="display: none;"></div>

<!-- Edit Family Profile Modal -->
<div id="srs-edit-family-modal" class="srs-modal" style="display: none;">
    <div class="srs-modal-content">
        <div class="srs-modal-header">
            <h3>Edit Family Profile</h3>
            <button class="srs-modal-close">&times;</button>
        </div>
        
        <div class="srs-modal-body">
            <form id="srs-edit-family-form" class="srs-form">
                <div class="srs-form-row srs-form-row-2">
                    <div class="srs-form-field">
                        <label for="edit-family-first-name">First Name</label>
                        <input type="text" id="edit-family-first-name" name="first_name" value="<?php echo esc_attr($family_data['first_name']); ?>" required>
                    </div>
                    
                    <div class="srs-form-field">
                        <label for="edit-family-last-name">Last Name</label>
                        <input type="text" id="edit-family-last-name" name="last_name" value="<?php echo esc_attr($family_data['last_name']); ?>" required>
                    </div>
                </div>
                
                <div class="srs-form-row srs-form-row-2">
                    <div class="srs-form-field">
                        <label for="edit-family-email">Email</label>
                        <input type="email" id="edit-family-email" name="email" value="<?php echo esc_attr($family_data['email']); ?>" required>
                    </div>
                    
                    <div class="srs-form-field">
                        <label for="edit-family-phone">Phone</label>
                        <input type="tel" id="edit-family-phone" name="phone" value="<?php echo esc_attr($family_data['phone']); ?>" required>
                    </div>
                </div>
                
                <div class="srs-form-row">
                    <div class="srs-form-field">
                        <label for="edit-family-address">Address</label>
                        <input type="text" id="edit-family-address" name="address" value="<?php echo esc_attr($family_data['address']); ?>" required>
                    </div>
                </div>
                
                <div class="srs-form-row srs-form-row-3">
                    <div class="srs-form-field">
                        <label for="edit-family-city">City</label>
                        <input type="text" id="edit-family-city" name="city" value="<?php echo esc_attr($family_data['city']); ?>" required>
                    </div>
                    
                    <div class="srs-form-field">
                        <label for="edit-family-state">State</label>
                        <input type="text" id="edit-family-state" name="state" value="<?php echo esc_attr($family_data['state']); ?>" required>
                    </div>
                    
                    <div class="srs-form-field">
                        <label for="edit-family-zip">Zip Code</label>
                        <input type="text" id="edit-family-zip" name="zip" value="<?php echo esc_attr($family_data['zip']); ?>" required>
                    </div>
                </div>
                
                <div class="srs-form-row">
                    <div class="srs-form-field">
                        <label for="edit-family-password">Password (leave blank to keep current)</label>
                        <input type="password" id="edit-family-password" name="password">
                    </div>
                </div>
                
                <div class="srs-form-actions">
                    <button type="submit" class="srs-button">Save Changes</button>
                    <button type="button" class="srs-button srs-button-outline srs-modal-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Child Modal -->
<div id="srs-add-child-modal" class="srs-modal" style="display: none;">
    <div class="srs-modal-content">
        <div class="srs-modal-header">
            <h3>Add Child</h3>
            <button class="srs-modal-close">&times;</button>
        </div>
        
        <div class="srs-modal-body">
            <form id="srs-add-child-form" class="srs-form">
                <div class="srs-form-row srs-form-row-2">
                    <div class="srs-form-field">
                        <label for="add-child-first-name">First Name</label>
                        <input type="text" id="add-child-first-name" name="first_name" required>
                    </div>
                    
                    <div class="srs-form-field">
                        <label for="add-child-last-name">Last Name</label>
                        <input type="text" id="add-child-last-name" name="last_name" value="<?php echo esc_attr($family_data['last_name']); ?>" required>
                    </div>
                </div>
                
                <div class="srs-form-row srs-form-row-2">
                    <div class="srs-form-field">
                        <label for="add-child-gender">Gender</label>
                        <select id="add-child-gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    
                    <div class="srs-form-field">
                        <label for="add-child-dob">Date of Birth</label>
                        <input type="date" id="add-child-dob" name="dob" required>
                    </div>
                </div>
                
                <div class="srs-form-row srs-form-row-2">
                    <div class="srs-form-field">
                        <label for="add-child-shirt-size">Shirt Size</label>
                        <select id="add-child-shirt-size" name="shirt_size">
                            <option value="">Select Shirt Size</option>
                            <option value="YXS">Youth Extra Small (YXS)</option>
                            <option value="YS">Youth Small (YS)</option>
                            <option value="YM">Youth Medium (YM)</option>
                            <option value="YL">Youth Large (YL)</option>
                            <option value="YXL">Youth Extra Large (YXL)</option>
                            <option value="AS">Adult Small (AS)</option>
                            <option value="AM">Adult Medium (AM)</option>
                            <option value="AL">Adult Large (AL)</option>
                            <option value="AXL">Adult Extra Large (AXL)</option>
                            <option value="A2XL">Adult 2XL (A2XL)</option>
                        </select>
                    </div>
                    
                    <div class="srs-form-field">
                        <label for="add-child-school">School</label>
                        <input type="text" id="add-child-school" name="school">
                    </div>
                </div>
                
                <div class="srs-form-row">
                    <div class="srs-form-field">
                        <label for="add-child-medical-issues">Medical Issues</label>
                        <textarea id="add-child-medical-issues" name="medical_issues" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="srs-form-row">
                    <div class="srs-form-field">
                        <label>Medical Insurance</label>
                        <div class="srs-radio-group">
                            <label class="srs-radio-label">
                                <input type="radio" name="medical_insurance" value="yes">
                                Yes
                            </label>
                            <label class="srs-radio-label">
                                <input type="radio" name="medical_insurance" value="no">
                                No
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="srs-form-actions">
                    <button type="submit" class="srs-button">Add Child</button>
                    <button type="button" class="srs-button srs-button-outline srs-modal-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Child Modal -->
<div id="srs-edit-child-modal" class="srs-modal" style="display: none;">
    <div class="srs-modal-content">
        <div class="srs-modal-header">
            <h3>Edit Child</h3>
            <button class="srs-modal-close">&times;</button>
        </div>
        
        <div class="srs-modal-body">
            <form id="srs-edit-child-form" class="srs-form">
                <input type="hidden" id="edit-child-id" name="child_id">
                
                <div class="srs-form-row srs-form-row-2">
                    <div class="srs-form-field">
                        <label for="edit-child-first-name">First Name</label>
                        <input type="text" id="edit-child-first-name" name="first_name" required>
                    </div>
                    
                    <div class="srs-form-field">
                        <label for="edit-child-last-name">Last Name</label>
                        <input type="text" id="edit-child-last-name" name="last_name" required>
                    </div>
                </div>
                
                <div class="srs-form-row srs-form-row-2">
                    <div class="srs-form-field">
                        <label for="edit-child-gender">Gender</label>
                        <select id="edit-child-gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    
                    <div class="srs-form-field">
                        <label for="edit-child-dob">Date of Birth</label>
                        <input type="date" id="edit-child-dob" name="dob" required>
                    </div>
                </div>
                
                <div class="srs-form-row srs-form-row-2">
                    <div class="srs-form-field">
                        <label for="edit-child-shirt-size">Shirt Size</label>
                        <select id="edit-child-shirt-size" name="shirt_size">
                            <option value="">Select Shirt Size</option>
                            <option value="YXS">Youth Extra Small (YXS)</option>
                            <option value="YS">Youth Small (YS)</option>
                            <option value="YM">Youth Medium (YM)</option>
                            <option value="YL">Youth Large (YL)</option>
                            <option value="YXL">Youth Extra Large (YXL)</option>
                            <option value="AS">Adult Small (AS)</option>
                            <option value="AM">Adult Medium (AM)</option>
                            <option value="AL">Adult Large (AL)</option>
                            <option value="AXL">Adult Extra Large (AXL)</option>
                            <option value="A2XL">Adult 2XL (A2XL)</option>
                        </select>
                    </div>
                    
                    <div class="srs-form-field">
                        <label for="edit-child-school">School</label>
                        <input type="text" id="edit-child-school" name="school">
                    </div>
                </div>
                
                <div class="srs-form-row">
                    <div class="srs-form-field">
                        <label for="edit-child-medical-issues">Medical Issues</label>
                        <textarea id="edit-child-medical-issues" name="medical_issues" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="srs-form-row">
                    <div class="srs-form-field">
                        <label>Medical Insurance</label>
                        <div class="srs-radio-group">
                            <label class="srs-radio-label">
                                <input type="radio" name="medical_insurance" value="yes">
                                Yes
                            </label>
                            <label class="srs-radio-label">
                                <input type="radio" name="medical_insurance" value="no">
                                No
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="srs-form-actions">
                    <button type="submit" class="srs-button">Save Changes</button>
                    <button type="button" class="srs-button srs-button-outline srs-modal-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Register Children Modal -->
<div id="srs-register-children-modal" class="srs-modal" style="display: none;">
    <div class="srs-modal-content">
        <div class="srs-modal-header">
            <h3>Register Children for <span id="srs-registration-form-title"></span></h3>
            <button class="srs-modal-close">&times;</button>
        </div>
        
        <div class="srs-modal-body">
            <form id="srs-register-children-form" class="srs-form">
                <input type="hidden" id="register-form-type" name="form_type">
                
                <div class="srs-form-field">
                    <label>Select Children to Register</label>
                    <div class="srs-children-checkboxes">
                        <?php foreach ($family_data['children'] as $child): ?>
                            <label class="srs-checkbox-label">
                                <input type="checkbox" name="child_ids[]" value="<?php echo esc_attr($child['id']); ?>">
                                <?php echo esc_html($child['first_name'] . ' ' . $child['last_name']); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="srs-social-media-waiver">
                    <label class="srs-checkbox-label">
                        <input type="checkbox" name="social_media_waiver" value="yes">
                        Do you give Laurel London Optimist Club permission to use your child's photo (team photo) for use on our website or social media?
                    </label>
                </div>
                
                <div class="srs-disclosure">
                    <label class="srs-checkbox-label">
                        <input type="checkbox" name="disclosure" value="1" required>
                        <?php echo wp_kses_post($global_settings['disclosure_text'] ?? 'I hereby certify that the information provided is true and accurate.'); ?>
                    </label>
                </div>
                
                <div class="srs-signature-section">
                    <label for="srs-signature">Parent/Guardian Signature</label>
                    <div class="srs-signature-pad">
                        <canvas id="srs-signature-canvas" width="400" height="200"></canvas>
                        <input type="hidden" id="srs-signature" name="signature" required>
                        <div class="srs-signature-controls">
                            <button type="button" class="srs-clear-signature">Clear Signature</button>
                        </div>
                    </div>
                </div>
                
                <div id="srs-fee-preview" class="srs-fee-preview" style="display: none;">
                    <h4>Registration Fees</h4>
                    <div id="srs-fee-breakdown" class="srs-fee-breakdown"></div>
                    <div id="srs-fee-total" class="srs-fee-total"></div>
                </div>
                
                <div class="srs-form-actions">
                    <button type="submit" class="srs-button">Complete Registration</button>
                    <button type="button" class="srs-button srs-button-outline srs-modal-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Initialize the signature pad
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof SignaturePad !== 'undefined') {
            const canvas = document.getElementById('srs-signature-canvas');
            const signatureInput = document.getElementById('srs-signature');
            const clearButton = document.querySelector('.srs-clear-signature');
            
            const signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgba(255, 255, 255, 0)',
                penColor: 'rgb(0, 0, 0)'
            });
            
            // Clear signature
            clearButton.addEventListener('click', function() {
                signaturePad.clear();
                signatureInput.value = '';
            });
            
            // Save signature to hidden input on form submit
            document.getElementById('srs-register-children-form').addEventListener('submit', function() {
                if (!signaturePad.isEmpty()) {
                    signatureInput.value = signaturePad.toDataURL();
                }
            });
        }
    });
</script>

<?php
/**
 * Family Login Template
 * 
 * Displays the login and registration forms for family accounts
 */
?>
<div class="srs-family-login-container">
    <div class="srs-login-tabs">
        <button class="srs-tab-button active" data-tab="login">Login</button>
        <button class="srs-tab-button" data-tab="register">Create Account</button>
    </div>
    
    <div class="srs-login-content">
        <!-- Login Form -->
        <div id="srs-login-tab" class="srs-tab-content active">
            <h2>Family Account Login</h2>
            <p class="srs-login-intro">Log in to access your family dashboard, manage child profiles, and register for activities.</p>
            
            <form id="srs-login-form" class="srs-form">
                <div class="srs-form-field">
                    <label for="login-email">Email</label>
                    <input type="email" id="login-email" name="email" required>
                </div>
                
                <div class="srs-form-field">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" required>
                </div>
                
                <div class="srs-form-actions">
                    <button type="submit" class="srs-button">Log In</button>
                </div>
            </form>
            
            <div class="srs-login-message" style="display: none;"></div>
        </div>
        
        <!-- Registration Form -->
        <div id="srs-register-tab" class="srs-tab-content">
            <h2>Create Family Account</h2>
            <p class="srs-login-intro">Create an account to manage your family's registrations and streamline future sign-ups.</p>
            
            <form id="srs-register-form" class="srs-form">
                <div class="srs-form-row srs-form-row-2">
                    <div class="srs-form-field">
                        <label for="register-first-name">First Name</label>
                        <input type="text" id="register-first-name" name="first_name" required>
                    </div>
                    
                    <div class="srs-form-field">
                        <label for="register-last-name">Last Name</label>
                        <input type="text" id="register-last-name" name="last_name" required>
                    </div>
                </div>
                
                <div class="srs-form-row srs-form-row-2">
                    <div class="srs-form-field">
                        <label for="register-email">Email</label>
                        <input type="email" id="register-email" name="email" required>
                    </div>
                    
                    <div class="srs-form-field">
                        <label for="register-phone">Phone</label>
                        <input type="tel" id="register-phone" name="phone" required>
                    </div>
                </div>
                
                <div class="srs-form-row">
                    <div class="srs-form-field">
                        <label for="register-address">Address</label>
                        <input type="text" id="register-address" name="address" required>
                    </div>
                </div>
                
                <div class="srs-form-row srs-form-row-3">
                    <div class="srs-form-field">
                        <label for="register-city">City</label>
                        <input type="text" id="register-city" name="city" required>
                    </div>
                    
                    <div class="srs-form-field">
                        <label for="register-state">State</label>
                        <input type="text" id="register-state" name="state" required>
                    </div>
                    
                    <div class="srs-form-field">
                        <label for="register-zip">Zip Code</label>
                        <input type="text" id="register-zip" name="zip" required>
                    </div>
                </div>
                
                <div class="srs-form-row srs-form-row-2">
                    <div class="srs-form-field">
                        <label for="register-password">Password</label>
                        <input type="password" id="register-password" name="password" required>
                    </div>
                    
                    <div class="srs-form-field">
                        <label for="register-confirm-password">Confirm Password</label>
                        <input type="password" id="register-confirm-password" name="confirm_password" required>
                    </div>
                </div>
                
                <div class="srs-form-actions">
                    <button type="submit" class="srs-button">Create Account</button>
                </div>
            </form>
            
            <div class="srs-register-message" style="display: none;"></div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab switching
        const tabButtons = document.querySelectorAll('.srs-tab-button');
        const tabContents = document.querySelectorAll('.srs-tab-content');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabName = this.getAttribute('data-tab');
                
                // Update active tab button
                tabButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Show selected tab content
                tabContents.forEach(content => content.classList.remove('active'));
                document.getElementById(`srs-${tabName}-tab`).classList.add('active');
            });
        });
        
        // Login form submission
        const loginForm = document.getElementById('srs-login-form');
        const loginMessage = document.querySelector('.srs-login-message');
        
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;
            
            // Validate form
            if (!email || !password) {
                showMessage(loginMessage, 'Please fill in all required fields.', 'error');
                return;
            }
            
            // Show loading state
            const submitButton = loginForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Logging in...';
            
            // Send AJAX request
            const formData = new FormData();
            formData.append('action', 'srs_parent_login');
            formData.append('nonce', srs_family_accounts.nonce);
            formData.append('email', email);
            formData.append('password', password);
            
            fetch(srs_family_accounts.ajax_url, {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(loginMessage, data.data.message, 'success');
                    
                    // Redirect to dashboard
                    setTimeout(function() {
                        window.location.href = data.data.redirect_url;
                    }, 1000);
                } else {
                    showMessage(loginMessage, data.data.message, 'error');
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage(loginMessage, 'An error occurred. Please try again.', 'error');
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            });
        });
        
        // Registration form submission
        const registerForm = document.getElementById('srs-register-form');
        const registerMessage = document.querySelector('.srs-register-message');
        
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('register-password').value;
            const confirmPassword = document.getElementById('register-confirm-password').value;
            
            // Validate passwords match
            if (password !== confirmPassword) {
                showMessage(registerMessage, 'Passwords do not match.', 'error');
                return;
            }
            
            // Show loading state
            const submitButton = registerForm.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Creating account...';
            
            // Send AJAX request
            const formData = new FormData(registerForm);
            formData.append('action', 'srs_parent_register');
            formData.append('nonce', srs_family_accounts.nonce);
            
            fetch(srs_family_accounts.ajax_url, {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage(registerMessage, data.data.message, 'success');
                    
                    // Redirect to dashboard
                    setTimeout(function() {
                        window.location.href = data.data.redirect_url;
                    }, 1000);
                } else {
                    showMessage(registerMessage, data.data.message, 'error');
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage(registerMessage, 'An error occurred. Please try again.', 'error');
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            });
        });
        
        // Helper function to show messages
        function showMessage(element, message, type) {
            element.textContent = message;
            element.className = 'srs-message';
            element.classList.add(`srs-message-${type}`);
            element.style.display = 'block';
        }
    });
</script>

<?php
/**
 * Class to handle payment processing
 */
class SRS_Payments {
    private $square_enabled;
    private $square_app_id;
    private $square_location_id;
    private $square_access_token;
    
    private $paypal_enabled;
    private $paypal_client_id;
    private $paypal_secret;
    
    public function __construct() {
        $settings = get_option('srs_global_settings', array());
        
        // Square settings
        $this->square_enabled = !empty($settings['square_enabled']);
        $this->square_app_id = $settings['square_app_id'] ?? '';
        $this->square_location_id = $settings['square_location_id'] ?? '';
        $this->square_access_token = $settings['square_access_token'] ?? '';
        
        // PayPal settings
        $this->paypal_enabled = !empty($settings['paypal_enabled']);
        $this->paypal_client_id = $settings['paypal_client_id'] ?? '';
        $this->paypal_secret = $settings['paypal_secret'] ?? '';
        
        // Enqueue payment scripts on frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_payment_scripts'));
        
        // AJAX handlers for payment processing
        add_action('wp_ajax_srs_process_square_payment', array($this, 'ajax_process_square_payment'));
        add_action('wp_ajax_nopriv_srs_process_square_payment', array($this, 'ajax_process_square_payment'));
        
        add_action('wp_ajax_srs_process_paypal_payment', array($this, 'ajax_process_paypal_payment'));
        add_action('wp_ajax_nopriv_srs_process_paypal_payment', array($this, 'ajax_process_paypal_payment'));
    }
    
    /**
     * Enqueue payment scripts on frontend
     */
    public function enqueue_payment_scripts() {
        // Only load on pages that have our registration form
        global $post;
        if (!is_a($post, 'WP_Post')) {
            return;
        }
        
        if (has_shortcode($post->post_content, 'srs_registration_form') || has_block('sports-registration/registration-form', $post->post_content)) {
            // Square SDK
            if ($this->square_enabled && !empty($this->square_app_id)) {
                wp_enqueue_script('square-web-payments-sdk', 'https://sandbox.web.squarecdn.com/v1/square.js', array(), null, true);
                wp_enqueue_script('srs-square-integration', SRS_PLUGIN_URL . 'public/js/srs-square.js', array('jquery', 'square-web-payments-sdk'), SRS_PLUGIN_VERSION, true);
                wp_localize_script('srs-square-integration', 'srs_square_params', array(
                    'app_id' => $this->square_app_id,
                    'location_id' => $this->square_location_id,
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('srs_square_nonce'),
                ));
            }
            
            // PayPal SDK
            if ($this->paypal_enabled && !empty($this->paypal_client_id)) {
                wp_enqueue_script('paypal-sdk', 'https://www.paypal.com/sdk/js?client-id=' . urlencode($this->paypal_client_id) . '&currency=USD', array(), null, true);
                wp_enqueue_script('srs-paypal-integration', SRS_PLUGIN_URL . 'public/js/srs-paypal.js', array('jquery', 'paypal-sdk'), SRS_PLUGIN_VERSION, true);
                wp_localize_script('srs-paypal-integration', 'srs_paypal_params', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('srs_paypal_nonce'),
                ));
            }
        }
    }
    
    /**
     * Process Square payment (AJAX)
     */
    public function ajax_process_square_payment() {
        check_ajax_referer('srs_square_nonce', 'nonce');
        
        $source_id = sanitize_text_field($_POST['source_id']);
        $form_data = json_decode(stripslashes($_POST['form_data']), true);
        
        if (empty($source_id) || empty($form_data)) {
            wp_send_json_error(array(
                'message' => 'Invalid payment data.',
            ));
            return;
        }
        
        $amount = floatval($form_data['payment_amount'] ?? 0);
        if ($amount <= 0) {
            wp_send_json_error(array(
                'message' => 'Invalid payment amount.',
            ));
            return;
        }
        
        // Process payment with Square API
        $result = $this->process_square_payment($source_id, $amount, $form_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => 'Payment failed: ' . $result->get_error_message(),
            ));
        } else {
            wp_send_json_success(array(
                'payment_id' => $result['id'],
                'payment_status' => 'paid',
                'message' => 'Payment successful!',
            ));
        }
    }
    
    /**
     * Process PayPal payment (AJAX)
     */
    public function ajax_process_paypal_payment() {
        check_ajax_referer('srs_paypal_nonce', 'nonce');
        
        $order_id = sanitize_text_field($_POST['order_id']);
        $form_data = json_decode(stripslashes($_POST['form_data']), true);
        
        if (empty($order_id) || empty($form_data)) {
            wp_send_json_error(array(
                'message' => 'Invalid payment data.',
            ));
            return;
        }
        
        // Process payment with PayPal API
        $result = $this->process_paypal_payment($order_id, $form_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => 'Payment failed: ' . $result->get_error_message(),
            ));
        } else {
            wp_send_json_success(array(
                'payment_id' => $result['id'],
                'payment_status' => 'paid',
                'message' => 'Payment successful!',
            ));
        }
    }
    
    /**
     * Process payment with Square API
     */
    private function process_square_payment($source_id, $amount, $form_data) {
        if (!class_exists('Square\SquareClient')) {
            // Include Square PHP SDK if available
            if (file_exists(SRS_PLUGIN_DIR . 'vendor/autoload.php')) {
                require_once SRS_PLUGIN_DIR . 'vendor/autoload.php';
            } else {
                return new WP_Error('square_sdk_missing', 'Square SDK is not available.');
            }
        }
        
        if (empty($this->square_access_token)) {
            return new WP_Error('configuration_error', 'Square Access Token is not configured.');
        }
        
        try {
            $client = new \Square\SquareClient([
                'accessToken' => $this->square_access_token,
                'environment' => 'sandbox', // Use 'production' for live environment
            ]);
            
            $payments_api = $client->getPaymentsApi();
            
            // Format amount for Square API (in cents)
            $amount_money = new \Square\Models\Money();
            $amount_money->setAmount(intval($amount * 100));
            $amount_money->setCurrency('USD');
            
            // Generate idempotency key to prevent duplicate charges
            $idempotency_key = uniqid();
            
            // Create customer information
            $customer_name = trim($form_data['first_name'] . ' ' . $form_data['last_name']);
            
            // Create payment request
            $payment_body = new \Square\Models\CreatePaymentRequest(
                $source_id,
                $idempotency_key,
                $amount_money
            );
            
            // Add customer details if available
            if (!empty($customer_name)) {
                $payment_body->setCustomerId($customer_name);
            }
            
            // Set note
            $note = 'Registration for ' . ucfirst($form_data['form_type']);
            $payment_body->setNote($note);
            
            // Make payment request
            $api_response = $payments_api->createPayment($payment_body);
            
            if ($api_response->isSuccess()) {
                $payment = $api_response->getResult()->getPayment();
                return array(
                    'id' => $payment->getId(),
                    'status' => 'paid',
                    'amount' => $payment->getAmountMoney()->getAmount() / 100,
                );
            } else {
                $errors = $api_response->getErrors();
                $error_message = '';
                foreach ($errors as $error) {
                    $error_message .= $error->getDetail() . ' ';
                }
                return new WP_Error('square_api_error', $error_message);
            }
        } catch (Exception $e) {
            return new WP_Error('square_exception', $e->getMessage());
        }
    }
    
    /**
     * Process payment with PayPal API
     */
    private function process_paypal_payment($order_id, $form_data) {
        if (empty($this->paypal_client_id) || empty($this->paypal_secret)) {
            return new WP_Error('configuration_error', 'PayPal credentials are not configured.');
        }
        
        try {
            // PayPal API endpoint
            $api_url = 'https://api.sandbox.paypal.com'; // Use api.paypal.com for production
            
            // Get access token
            $token_response = wp_remote_post($api_url . '/v1/oauth2/token', array(
                'method' => 'POST',
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode($this->paypal_client_id . ':' . $this->paypal_secret),
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ),
                'body' => 'grant_type=client_credentials',
            ));
            
            if (is_wp_error($token_response)) {
                return $token_response;
            }
            
            $token_data = json_decode(wp_remote_retrieve_body($token_response), true);
            if (empty($token_data['access_token'])) {
                return new WP_Error('paypal_auth_error', 'Failed to authenticate with PayPal.');
            }
            
            $access_token = $token_data['access_token'];
            
            // Get order details
            $order_response = wp_remote_get($api_url . '/v2/checkout/orders/' . $order_id, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json',
                ),
            ));
            
            if (is_wp_error($order_response)) {
                return $order_response;
            }
            
            $order_data = json_decode(wp_remote_retrieve_body($order_response), true);
            
            // Check order status
            if ($order_data['status'] !== 'APPROVED' && $order_data['status'] !== 'COMPLETED') {
                return new WP_Error('paypal_order_error', 'Order is not approved or completed.');
            }
            
            // Capture the payment
            $capture_response = wp_remote_post($api_url . '/v2/checkout/orders/' . $order_id . '/capture', array(
                'method' => 'POST',
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json',
                ),
            ));
            
            if (is_wp_error($capture_response)) {
                return $capture_response;
            }
            
            $capture_data = json_decode(wp_remote_retrieve_body($capture_response), true);
            
            if ($capture_data['status'] !== 'COMPLETED') {
                return new WP_Error('paypal_capture_error', 'Failed to capture payment.');
            }
            
            // Payment successful
            return array(
                'id' => $capture_data['id'],
                'status' => 'paid',
                'amount' => $capture_data['purchase_units'][0]['payments']['captures'][0]['amount']['value'],
            );
        } catch (Exception $e) {
            return new WP_Error('paypal_exception', $e->getMessage());
        }
    }
    
    /**
     * Process payment based on method
     */
    public function process_payment($form_data) {
        $payment_method = $form_data['payment_method'] ?? '';
        
        switch ($payment_method) {
            case 'square':
                return $this->process_square_payment(
                    $form_data['square_nonce'],
                    floatval($form_data['payment_amount']),
                    $form_data
                );
                
            case 'paypal':
                return $this->process_paypal_payment(
                    $form_data['paypal_order_id'],
                    $form_data
                );
                
            default:
                return new WP_Error('invalid_method', 'Invalid payment method.');
        }
    }
}

/**
 * Square payment processing JavaScript
 */
function srs_square_js() {
    ?>
    // Square payment integration
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Square
        if (typeof Square === 'undefined') {
            console.error('Square SDK not loaded');
            return;
        }
        
        const forms = document.querySelectorAll('.srs-registration-form');
        forms.forEach(function(form) {
            const squarePaymentForm = form.querySelector('#square-payment-form');
            if (!squarePaymentForm) return;
            
            const submitButton = form.querySelector('.srs-submit-button');
            
            const applicationId = srs_square_params.app_id;
            const locationId = srs_square_params.location_id;
            
            async function initializeSquare() {
                if (!applicationId || !locationId) {
                    console.error('Square credentials not configured');
                    return;
                }
                
                try {
                    const payments = Square.payments(applicationId, locationId);
                    const card = await payments.card();
                    await card.attach('#square-payment-form');
                    
                    // Handle form submission with Square
                    form.addEventListener('submit', async function(event) {
                        const paymentMethod = form.querySelector('input[name="payment_method"]:checked');
                        if (paymentMethod && paymentMethod.value === 'square') {
                            event.preventDefault();
                            
                            // Show loading state
                            submitButton.disabled = true;
                            submitButton.textContent = 'Processing Payment...';
                            
                            try {
                                const result = await card.tokenize();
                                if (result.status === 'OK') {
                                    // Get form data
                                    const formData = new FormData(form);
                                    const formDataObj = {};
                                    formData.forEach((value, key) => {
                                        formDataObj[key] = value;
                                    });
                                    
                                    // Send payment data to server
                                    const response = await fetch(srs_square_params.ajax_url, {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded',
                                        },
                                        body: new URLSearchParams({
                                            action: 'srs_process_square_payment',
                                            nonce: srs_square_params.nonce,
                                            source_id: result.token,
                                            form_data: JSON.stringify(formDataObj)
                                        })
                                    });
                                    
                                    const responseData = await response.json();
                                    
                                    if (responseData.success) {
                                        // Payment successful, submit form data
                                        formData.append('payment_id', responseData.data.payment_id);
                                        formData.append('payment_status', responseData.data.payment_status);
                                        
                                        // Submit the registration data
                                        submitFormData(form, formData);
                                    } else {
                                        // Payment failed
                                        const messages = form.parentNode.querySelector('.srs-form-messages');
                                        messages.style.display = 'block';
                                        messages.innerHTML = '<div class="srs-error-message">' + responseData.data.message + '</div>';
                                        messages.scrollIntoView({behavior: 'smooth'});
                                        
                                        submitButton.disabled = false;
                                        submitButton.textContent = 'Submit Registration';
                                    }
                                } else {
                                    // Card tokenization failed
                                    const messages = form.parentNode.querySelector('.srs-form-messages');
                                    messages.style.display = 'block';
                                    messages.innerHTML = '<div class="srs-error-message">Card validation failed: ' + result.errors[0].message + '</div>';
                                    messages.scrollIntoView({behavior: 'smooth'});
                                    
                                    submitButton.disabled = false;
                                    submitButton.textContent = 'Submit Registration';
                                }
                            } catch (e) {
                                console.error('Square payment error:', e);
                                
                                const messages = form.parentNode.querySelector('.srs-form-messages');
                                messages.style.display = 'block';
                                messages.innerHTML = '<div class="srs-error-message">Payment processing error. Please try again.</div>';
                                messages.scrollIntoView({behavior: 'smooth'});
                                
                                submitButton.disabled = false;
                                submitButton.textContent = 'Submit Registration';
                            }
                        }
                    });
                } catch (e) {
                    console.error('Failed to initialize Square payments:', e);
                }
            }
            
            initializeSquare();
        });
    });
    <?php
}

/**
 * PayPal payment processing JavaScript
 */
function srs_paypal_js() {
    ?>
    // PayPal payment integration
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize PayPal
        if (typeof paypal === 'undefined') {
            console.error('PayPal SDK not loaded');
            return;
        }
        
        const forms = document.querySelectorAll('.srs-registration-form');
        forms.forEach(function(form) {
            const paypalContainer = form.querySelector('#paypal-payment-form');
            if (!paypalContainer) return;
            
            const submitButton = form.querySelector('.srs-submit-button');
            
            // Get payment amount
            const paymentAmountField = form.querySelector('input[name="payment_amount"]');
            const paymentAmount = paymentAmountField ? paymentAmountField.value : '0';
            
            if (parseFloat(paymentAmount) <= 0) {
                return;
            }
            
            // Initialize PayPal buttons
            paypal.Buttons({
                createOrder: function(data, actions) {
                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                currency_code: 'USD',
                                value: paymentAmount
                            },
                            description: 'Registration Fee'
                        }]
                    });
                },
                onApprove: function(data, actions) {
                    // Show loading state
                    submitButton.disabled = true;
                    submitButton.textContent = 'Processing Payment...';
                    
                    return actions.order.capture().then(function(details) {
                        // Get form data
                        const formData = new FormData(form);
                        const formDataObj = {};
                        formData.forEach((value, key) => {
                            formDataObj[key] = value;
                        });
                        
                        // Send payment data to server
                        fetch(srs_paypal_params.ajax_url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: new URLSearchParams({
                                action: 'srs_process_paypal_payment',
                                nonce: srs_paypal_params.nonce,
                                order_id: data.orderID,
                                form_data: JSON.stringify(formDataObj)
                            })
                        })
                        .then(response => response.json())
                        .then(responseData => {
                            if (responseData.success) {
                                // Payment successful, submit form data
                                formData.append('payment_id', responseData.data.payment_id);
                                formData.append('payment_status', responseData.data.payment_status);
                                
                                // Submit the registration data
                                submitFormData(form, formData);
                            } else {
                                // Payment failed
                                const messages = form.parentNode.querySelector('.srs-form-messages');
                                messages.style.display = 'block';
                                messages.innerHTML = '<div class="srs-error-message">' + responseData.data.message + '</div>';
                                messages.scrollIntoView({behavior: 'smooth'});
                                
                                submitButton.disabled = false;
                                submitButton.textContent = 'Submit Registration';
                            }
                        })
                        .catch(error => {
                            console.error('PayPal payment error:', error);
                            
                            const messages = form.parentNode.querySelector('.srs-form-messages');
                            messages.style.display = 'block';
                            messages.innerHTML = '<div class="srs-error-message">Payment processing error. Please try again.</div>';
                            messages.scrollIntoView({behavior: 'smooth'});
                            
                            submitButton.disabled = false;
                            submitButton.textContent = 'Submit Registration';
                        });
                    });
                },
                onError: function(err) {
                    console.error('PayPal error:', err);
                    
                    const messages = form.parentNode.querySelector('.srs-form-messages');
                    messages.style.display = 'block';
                    messages.innerHTML = '<div class="srs-error-message">PayPal error. Please try again.</div>';
                    messages.scrollIntoView({behavior: 'smooth'});
                }
            }).render(paypalContainer);
            
            // Handle form submission with PayPal
            form.addEventListener('submit', function(event) {
                const paymentMethod = form.querySelector('input[name="payment_method"]:checked');
                if (paymentMethod && paymentMethod.value === 'paypal') {
                    event.preventDefault();
                    // PayPal is handled by the button click
                }
            });
        });
    });
    <?php
}

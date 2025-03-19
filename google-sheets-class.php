<?php
/**
 * Google Sheets Integration class
 */
class SRS_Google_Sheet {
    private $spreadsheet_id;
    private $client;
    private $service;
    private $enabled;
    
    public function __construct() {
        $settings = get_option('srs_global_settings', array());
        $this->enabled = !empty($settings['google_sheets_enabled']);
        $this->spreadsheet_id = $settings['google_sheets_id'] ?? '';
        
        if ($this->enabled && !empty($this->spreadsheet_id)) {
            $this->init_google_client($settings);
        }
    }
    
    /**
     * Initialize Google API client
     */
    private function init_google_client($settings) {
        if (!class_exists('Google_Client') && file_exists(SRS_PLUGIN_DIR . 'vendor/autoload.php')) {
            require_once SRS_PLUGIN_DIR . 'vendor/autoload.php';
        }
        
        if (!class_exists('Google_Client')) {
            $this->enabled = false;
            error_log('Sports Registration System: Google API Client library not found.');
            return;
        }
        
        try {
            // Initialize Google Client
            $this->client = new Google_Client();
            $this->client->setApplicationName('Sports Registration System');
            $this->client->setScopes(Google_Service_Sheets::SPREADSHEETS);
            $this->client->setAccessType('offline');
            
            // Load service account credentials
            if (!empty($settings['google_service_account_json'])) {
                $service_account_json = json_decode($settings['google_service_account_json'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->client->setAuthConfig($service_account_json);
                } else {
                    $this->enabled = false;
                    error_log('Sports Registration System: Invalid Google service account JSON.');
                    return;
                }
            } else {
                $this->enabled = false;
                error_log('Sports Registration System: Google service account JSON not provided.');
                return;
            }
            
            // Create Google Sheets service
            $this->service = new Google_Service_Sheets($this->client);
        } catch (Exception $e) {
            $this->enabled = false;
            error_log('Sports Registration System: Google Sheets initialization error: ' . $e->getMessage());
        }
    }
    
    /**
     * Add a row to the Google Sheet
     */
    public function add_row($form_data) {
        if (!$this->enabled || empty($this->spreadsheet_id) || !$this->service) {
            return false;
        }
        
        try {
            // Get the form type for the sheet name
            $form_type = sanitize_text_field($form_data['form_type']);
            $sheet_name = ucfirst($form_type);
            
            // Check if the sheet exists, if not create it
            $sheet_id = $this->get_or_create_sheet($sheet_name);
            
            // Get headers
            $headers = $this->get_headers($form_type);
            
            // Get the next row number
            $range = $sheet_name . '!A:A';
            $response = $this->service->spreadsheets_values->get($this->spreadsheet_id, $range);
            $next_row = count($response->getValues()) + 1;
            
            if ($next_row === 1) {
                // Sheet is empty, add headers first
                $header_range = $sheet_name . '!A1:' . $this->get_column_letter(count($headers)) . '1';
                $header_values = array($headers);
                $header_body = new Google_Service_Sheets_ValueRange([
                    'values' => $header_values
                ]);
                $this->service->spreadsheets_values->update(
                    $this->spreadsheet_id,
                    $header_range,
                    $header_body,
                    ['valueInputOption' => 'RAW']
                );
                $next_row = 2;
            }
            
            // Prepare row data
            $row_data = array();
            foreach ($headers as $header) {
                $field_key = $this->get_field_key_from_header($header);
                $row_data[] = $form_data[$field_key] ?? '';
            }
            
            // Add additional fields not in headers
            if (!in_array('Submission Date', $headers)) {
                $headers[] = 'Submission Date';
                $row_data[] = current_time('Y-m-d H:i:s');
            }
            
            if (!in_array('Payment Status', $headers) && isset($form_data['payment_status'])) {
                $headers[] = 'Payment Status';
                $row_data[] = $form_data['payment_status'];
            }
            
            if (!in_array('Payment ID', $headers) && isset($form_data['payment_id'])) {
                $headers[] = 'Payment ID';
                $row_data[] = $form_data['payment_id'];
            }
            
            // Update the sheet with the new row
            $row_range = $sheet_name . '!A' . $next_row . ':' . $this->get_column_letter(count($row_data)) . $next_row;
            $row_values = array($row_data);
            $row_body = new Google_Service_Sheets_ValueRange([
                'values' => $row_values
            ]);
            
            $result = $this->service->spreadsheets_values->update(
                $this->spreadsheet_id,
                $row_range,
                $row_body,
                ['valueInputOption' => 'RAW']
            );
            
            return $result->getUpdatedCells() > 0;
        } catch (Exception $e) {
            error_log('Sports Registration System: Google Sheets error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get or create a sheet
     */
    private function get_or_create_sheet($sheet_name) {
        try {
            // Get spreadsheet info
            $spreadsheet = $this->service->spreadsheets->get($this->spreadsheet_id);
            $sheets = $spreadsheet->getSheets();
            
            // Check if sheet exists
            $sheet_id = null;
            foreach ($sheets as $sheet) {
                if ($sheet->getProperties()->getTitle() === $sheet_name) {
                    $sheet_id = $sheet->getProperties()->getSheetId();
                    break;
                }
            }
            
            // Create sheet if it doesn't exist
            if ($sheet_id === null) {
                $requests = array(
                    new Google_Service_Sheets_Request([
                        'addSheet' => [
                            'properties' => [
                                'title' => $sheet_name
                            ]
                        ]
                    ])
                );
                
                $batch_update_request = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
                    'requests' => $requests
                ]);
                
                $response = $this->service->spreadsheets->batchUpdate($this->spreadsheet_id, $batch_update_request);
                $sheet_id = $response->getReplies()[0]->getAddSheet()->getProperties()->getSheetId();
            }
            
            return $sheet_id;
        } catch (Exception $e) {
            error_log('Sports Registration System: Google Sheets error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get headers for a form type
     */
    private function get_headers($form_type) {
        $settings = get_option('srs_' . $form_type . '_settings', array());
        $required_fields = $settings['required_fields'] ?? array();
        
        // Base headers that should always be included
        $headers = array(
            'First Name',
            'Last Name',
            'Gender',
            'Shirt Size',
            'Address',
            'City',
            'State',
            'Zip',
            'Phone',
            'Date of Birth',
            'School'
        );
        
        // Add optional headers if required
        if (in_array('medical_issues', $required_fields)) {
            $headers[] = 'Medical Issues';
        }
        
        if (in_array('medical_insurance', $required_fields)) {
            $headers[] = 'Medical Insurance';
        }
        
        if (in_array('siblings', $required_fields)) {
            $headers[] = 'Siblings';
        }
        
        if (in_array('emergency_contact', $required_fields)) {
            $headers[] = 'Emergency Contact';
        }
        
        if (in_array('emergency_phone', $required_fields)) {
            $headers[] = 'Emergency Phone';
        }
        
        if (in_array('social_media_waiver', $required_fields)) {
            $headers[] = 'Social Media Waiver';
        }
        
        // Add metadata headers
        $headers[] = 'Submission Date';
        $headers[] = 'Payment Status';
        $headers[] = 'Payment ID';
        
        return $headers;
    }
    
    /**
     * Convert header to field key
     */
    private function get_field_key_from_header($header) {
        $mapping = array(
            'First Name' => 'first_name',
            'Last Name' => 'last_name',
            'Gender' => 'gender',
            'Shirt Size' => 'shirt_size',
            'Address' => 'address',
            'City' => 'city',
            'State' => 'state',
            'Zip' => 'zip',
            'Phone' => 'phone',
            'Date of Birth' => 'dob',
            'School' => 'school',
            'Medical Issues' => 'medical_issues',
            'Medical Insurance' => 'medical_insurance',
            'Siblings' => 'siblings',
            'Emergency Contact' => 'emergency_contact',
            'Emergency Phone' => 'emergency_phone',
            'Social Media Waiver' => 'social_media_waiver',
            'Submission Date' => 'submission_date',
            'Payment Status' => 'payment_status',
            'Payment ID' => 'payment_id',
        );
        
        return $mapping[$header] ?? strtolower(str_replace(' ', '_', $header));
    }
    
    /**
     * Convert column number to letter (A, B, C, ... AA, AB, etc.)
     */
    private function get_column_letter($column_number) {
        $column_letter = '';
        
        while ($column_number > 0) {
            $modulo = ($column_number - 1) % 26;
            $column_letter = chr(65 + $modulo) . $column_letter;
            $column_number = floor(($column_number - $modulo) / 26);
        }
        
        return $column_letter;
    }
    
    /**
     * Test Google Sheets connection
     */
    public function test_connection() {
        if (!$this->enabled || empty($this->spreadsheet_id) || !$this->service) {
            return new WP_Error('not_configured', 'Google Sheets integration is not properly configured.');
        }
        
        try {
            // Try to get spreadsheet info
            $spreadsheet = $this->service->spreadsheets->get($this->spreadsheet_id);
            
            return array(
                'success' => true,
                'message' => 'Successfully connected to Google Sheets!',
                'spreadsheet_title' => $spreadsheet->getProperties()->getTitle(),
            );
        } catch (Exception $e) {
            return new WP_Error('connection_failed', 'Failed to connect to Google Sheets: ' . $e->getMessage());
        }
    }
}

/**
 * Admin Google Sheets connection test AJAX handler
 */
function srs_admin_test_google_sheets_connection() {
    check_ajax_referer('srs_admin_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array(
            'message' => 'You do not have permission to perform this action.',
        ));
        return;
    }
    
    $sheets = new SRS_Google_Sheet();
    $result = $sheets->test_connection();
    
    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message(),
        ));
    } else {
        wp_send_json_success(array(
            'message' => $result['message'],
            'spreadsheet_title' => $result['spreadsheet_title'],
        ));
    }
}
add_action('wp_ajax_srs_test_google_sheets', 'srs_admin_test_google_sheets_connection');

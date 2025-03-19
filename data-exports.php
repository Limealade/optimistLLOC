<?php
/**
 * Data exports and reports for family accounts and registrations
 */

/**
 * Add export options to admin menu
 */
function srs_add_exports_menu() {
    add_submenu_page(
        'sports-registration',
        'Reports & Exports',
        'Reports & Exports',
        'manage_options',
        'sports-registration-reports',
        'srs_display_reports_page'
    );
}
add_action('admin_menu', 'srs_add_exports_menu');

/**
 * Display reports and exports page
 */
function srs_display_reports_page() {
    // Handle export requests
    if (isset($_POST['export_data']) && check_admin_referer('srs_export_data')) {
        $export_type = sanitize_text_field($_POST['export_type'] ?? '');
        $export_format = sanitize_text_field($_POST['export_format'] ?? '');
        $sport_type = sanitize_text_field($_POST['sport_type'] ?? '');
        $season_id = intval($_POST['season_id'] ?? 0);
        
        srs_process_export_request($export_type, $export_format, $sport_type, $season_id);
    }
    
    // Get seasons for filters
    $seasons = get_posts(array(
        'post_type' => 'srs_season',
        'posts_per_page' => -1,
        'orderby' => 'meta_value',
        'meta_key' => 'start_date',
        'order' => 'DESC',
    ));
    
    ?>
    <div class="wrap">
        <h1><?php _e('Reports & Exports', 'sports-registration'); ?></h1>
        
        <div class="srs-admin-section">
            <h2><?php _e('Export Data', 'sports-registration'); ?></h2>
            <p><?php _e('Generate reports and export data from the registration system.', 'sports-registration'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('srs_export_data'); ?>
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="export_type"><?php _e('Export Type', 'sports-registration'); ?></label></th>
                            <td>
                                <select name="export_type" id="export_type">
                                    <option value="registrations"><?php _e('Registrations', 'sports-registration'); ?></option>
                                    <option value="families"><?php _e('Family Accounts', 'sports-registration'); ?></option>
                                    <option value="children"><?php _e('Child Profiles', 'sports-registration'); ?></option>
                                    <option value="team_roster"><?php _e('Team Roster', 'sports-registration'); ?></option>
                                    <option value="financial"><?php _e('Financial Report', 'sports-registration'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr class="export-filter sport-filter">
                            <th scope="row"><label for="sport_type"><?php _e('Sport Type', 'sports-registration'); ?></label></th>
                            <td>
                                <select name="sport_type" id="sport_type">
                                    <option value=""><?php _e('All Sports', 'sports-registration'); ?></option>
                                    <option value="basketball"><?php _e('Basketball', 'sports-registration'); ?></option>
                                    <option value="soccer"><?php _e('Soccer', 'sports-registration'); ?></option>
                                    <option value="cheerleading"><?php _e('Cheerleading', 'sports-registration'); ?></option>
                                    <option value="volleyball"><?php _e('Volleyball', 'sports-registration'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr class="export-filter season-filter">
                            <th scope="row"><label for="season_id"><?php _e('Season', 'sports-registration'); ?></label></th>
                            <td>
                                <select name="season_id" id="season_id">
                                    <option value=""><?php _e('All Seasons', 'sports-registration'); ?></option>
                                    <?php foreach ($seasons as $season): ?>
                                        <option value="<?php echo esc_attr($season->ID); ?>"><?php echo esc_html($season->post_title); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="export_format"><?php _e('Export Format', 'sports-registration'); ?></label></th>
                            <td>
                                <select name="export_format" id="export_format">
                                    <option value="csv"><?php _e('CSV', 'sports-registration'); ?></option>
                                    <option value="excel"><?php _e('Excel', 'sports-registration'); ?></option>
                                    <option value="pdf"><?php _e('PDF', 'sports-registration'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p class="submit">
                    <input type="submit" name="export_data" id="export_data" class="button button-primary" value="<?php _e('Generate Export', 'sports-registration'); ?>">
                </p>
            </form>
        </div>
        
        <div class="srs-admin-section">
            <h2><?php _e('Registration Summary', 'sports-registration'); ?></h2>
            
            <div class="srs-summary-stats">
                <?php
                global $wpdb;
                $table_name = $wpdb->prefix . 'srs_registrations';
                $current_year = date('Y-01-01');
                
                // Total registrations this year
                $total_registrations = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_name WHERE created_at >= %s",
                        $current_year
                    )
                );
                
                // Registrations by sport type
                $sport_counts = array();
                $sport_types = array('basketball', 'soccer', 'cheerleading', 'volleyball');
                
                foreach ($sport_types as $sport) {
                    $count = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT COUNT(*) FROM $table_name WHERE form_type = %s AND created_at >= %s",
                            $sport,
                            $current_year
                        )
                    );
                    
                    $sport_counts[$sport] = $count;
                }
                
                // Payment stats
                $payment_stats = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT 
                            COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_count,
                            SUM(CASE WHEN payment_status = 'paid' THEN payment_amount ELSE 0 END) as total_paid,
                            COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_count,
                            SUM(CASE WHEN payment_status = 'pending' THEN payment_amount ELSE 0 END) as total_pending
                        FROM $table_name 
                        WHERE created_at >= %s",
                        $current_year
                    )
                );
                
                // Family account stats
                $family_count = wp_count_posts('srs_family')->publish;
                $child_count = wp_count_posts('srs_child')->publish;
                ?>
                
                <div class="srs-summary-row">
                    <div class="srs-summary-card">
                        <div class="srs-summary-value"><?php echo esc_html($total_registrations); ?></div>
                        <div class="srs-summary-label"><?php _e('Total Registrations', 'sports-registration'); ?></div>
                    </div>
                    
                    <div class="srs-summary-card">
                        <div class="srs-summary-value"><?php echo esc_html($payment_stats->paid_count); ?></div>
                        <div class="srs-summary-label"><?php _e('Paid Registrations', 'sports-registration'); ?></div>
                    </div>
                    
                    <div class="srs-summary-card">
                        <div class="srs-summary-value">$<?php echo esc_html(number_format($payment_stats->total_paid, 2)); ?></div>
                        <div class="srs-summary-label"><?php _e('Total Revenue', 'sports-registration'); ?></div>
                    </div>
                    
                    <div class="srs-summary-card">
                        <div class="srs-summary-value"><?php echo esc_html($family_count); ?></div>
                        <div class="srs-summary-label"><?php _e('Family Accounts', 'sports-registration'); ?></div>
                    </div>
                    
                    <div class="srs-summary-card">
                        <div class="srs-summary-value"><?php echo esc_html($child_count); ?></div>
                        <div class="srs-summary-label"><?php _e('Child Profiles', 'sports-registration'); ?></div>
                    </div>
                </div>
                
                <div class="srs-sports-breakdown">
                    <h3><?php _e('Registrations by Sport', 'sports-registration'); ?></h3>
                    
                    <div class="srs-sport-bars">
                        <?php foreach ($sport_counts as $sport => $count): ?>
                            <div class="srs-sport-bar-wrapper">
                                <div class="srs-sport-label"><?php echo esc_html(ucfirst($sport)); ?></div>
                                <div class="srs-sport-bar-container">
                                    <?php if ($total_registrations > 0): ?>
                                        <div class="srs-sport-bar" style="width: <?php echo esc_attr(($count / $total_registrations) * 100); ?>%;">
                                            <span class="srs-sport-count"><?php echo esc_html($count); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="srs-sport-bar" style="width: 0;">
                                            <span class="srs-sport-count">0</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        jQuery(document).ready(function($) {
            // Show/hide filters based on export type
            $('#export_type').on('change', function() {
                var exportType = $(this).val();
                
                if (exportType === 'registrations' || exportType === 'team_roster' || exportType === 'financial') {
                    $('.sport-filter, .season-filter').show();
                } else if (exportType === 'families' || exportType === 'children') {
                    $('.sport-filter, .season-filter').hide();
                }
            });
            
            // Initialize visibility
            $('#export_type').trigger('change');
        });
    </script>
    <?php
}

/**
 * Process export request
 */
function srs_process_export_request($export_type, $export_format, $sport_type, $season_id) {
    global $wpdb;
    
    // Validate export type
    $valid_types = array('registrations', 'families', 'children', 'team_roster', 'financial');
    if (!in_array($export_type, $valid_types)) {
        wp_die(__('Invalid export type.', 'sports-registration'));
    }
    
    // Validate export format
    $valid_formats = array('csv', 'excel', 'pdf');
    if (!in_array($export_format, $valid_formats)) {
        wp_die(__('Invalid export format.', 'sports-registration'));
    }
    
    // Get export data
    $data = array();
    $headers = array();
    
    switch ($export_type) {
        case 'registrations':
            list($headers, $data) = srs_get_registrations_export_data($sport_type, $season_id);
            break;
        
        case 'families':
            list($headers, $data) = srs_get_families_export_data();
            break;
        
        case 'children':
            list($headers, $data) = srs_get_children_export_data();
            break;
        
        case 'team_roster':
            list($headers, $data) = srs_get_team_roster_export_data($sport_type, $season_id);
            break;
        
        case 'financial':
            list($headers, $data) = srs_get_financial_export_data($sport_type, $season_id);
            break;
    }
    
    // Generate export file
    switch ($export_format) {
        case 'csv':
            srs_generate_csv_export($headers, $data, $export_type);
            break;
        
        case 'excel':
            srs_generate_excel_export($headers, $data, $export_type);
            break;
        
        case 'pdf':
            srs_generate_pdf_export($headers, $data, $export_type);
            break;
    }
    
    exit;
}

/**
 * Get registrations export data
 */
function srs_get_registrations_export_data($sport_type, $season_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'srs_registrations';
    
    $query = "SELECT * FROM $table_name WHERE 1=1";
    $query_args = array();
    
    if (!empty($sport_type)) {
        $query .= " AND form_type = %s";
        $query_args[] = $sport_type;
    }
    
    if (!empty($season_id)) {
        // Get season date range
        $start_date = get_post_meta($season_id, 'start_date', true);
        $end_date = get_post_meta($season_id, 'end_date', true);
        
        if (!empty($start_date) && !empty($end_date)) {
            $query .= " AND created_at BETWEEN %s AND %s";
            $query_args[] = $start_date . ' 00:00:00';
            $query_args[] = $end_date . ' 23:59:59';
        }
    }
    
    $query .= " ORDER BY created_at DESC";
    
    if (!empty($query_args)) {
        $registrations = $wpdb->get_results($wpdb->prepare($query, $query_args));
    } else {
        $registrations = $wpdb->get_results($query);
    }
    
    $headers = array(
        'ID',
        'Sport',
        'First Name',
        'Last Name',
        'Gender',
        'Date of Birth',
        'Age',
        'Shirt Size',
        'Address',
        'City',
        'State',
        'Zip',
        'Phone',
        'Parent/Guardian',
        'Parent Email',
        'School',
        'Medical Issues',
        'Medical Insurance',
        'Emergency Contact',
        'Emergency Phone',
        'Social Media Waiver',
        'Registration Date',
        'Payment Status',
        'Payment Amount'
    );
    
    $data = array();
    
    foreach ($registrations as $registration) {
        $form_data = json_decode($registration->form_data, true);
        
        // Calculate age
        $dob = isset($form_data['dob']) ? $form_data['dob'] : '';
        $age = '';
        
        if (!empty($dob)) {
            $dob_obj = new DateTime($dob);
            $today = new DateTime();
            $age = $dob_obj->diff($today)->y;
        }
        
        $row = array(
            $registration->id,
            ucfirst($registration->form_type),
            $registration->first_name,
            $registration->last_name,
            isset($form_data['gender']) ? ucfirst($form_data['gender']) : '',
            isset($form_data['dob']) ? date('m/d/Y', strtotime($form_data['dob'])) : '',
            $age,
            $form_data['shirt_size'] ?? '',
            $form_data['address'] ?? '',
            $form_data['city'] ?? '',
            $form_data['state'] ?? '',
            $form_data['zip'] ?? '',
            $form_data['phone'] ?? '',
            isset($form_data['parent_first_name']) && isset($form_data['parent_last_name']) ? $form_data['parent_first_name'] . ' ' . $form_data['parent_last_name'] : '',
            $form_data['parent_email'] ?? '',
            $form_data['school'] ?? '',
            $form_data['medical_issues'] ?? '',
            $form_data['medical_insurance'] ?? '',
            $form_data['emergency_contact'] ?? '',
            $form_data['emergency_phone'] ?? '',
            $form_data['social_media_waiver'] ?? '',
            date('m/d/Y', strtotime($registration->created_at)),
            ucfirst($registration->payment_status),
            $registration->payment_amount ? '$' . number_format($registration->payment_amount, 2) : ''
        );
        
        $data[] = $row;
    }
    
    return array($headers, $data);
}

/**
 * Get families export data
 */
function srs_get_families_export_data() {
    $args = array(
        'post_type' => 'srs_family',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    );
    
    $families = get_posts($args);
    
    $headers = array(
        'ID',
        'First Name',
        'Last Name',
        'Email',
        'Phone',
        'Address',
        'City',
        'State',
        'ZIP',
        'Children Count',
        'Created Date'
    );
    
    $data = array();
    
    foreach ($families as $family) {
        $first_name = get_post_meta($family->ID, 'first_name', true);
        $last_name = get_post_meta($family->ID, 'last_name', true);
        $email = get_post_meta($family->ID, 'email', true);
        $phone = get_post_meta($family->ID, 'phone', true);
        $address = get_post_meta($family->ID, 'address', true);
        $city = get_post_meta($family->ID, 'city', true);
        $state = get_post_meta($family->ID, 'state', true);
        $zip = get_post_meta($family->ID, 'zip', true);
        $children = get_post_meta($family->ID, 'children', true);
        $children_count = is_array($children) ? count($children) : 0;
        
        $row = array(
            $family->ID,
            $first_name,
            $last_name,
            $email,
            $phone,
            $address,
            $city,
            $state,
            $zip,
            $children_count,
            get_the_date('m/d/Y', $family->ID)
        );
        
        $data[] = $row;
    }
    
    return array($headers, $data);
}

/**
 * Get children export data
 */
function srs_get_children_export_data() {
    $args = array(
        'post_type' => 'srs_child',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    );
    
    $children = get_posts($args);
    
    $headers = array(
        'ID',
        'First Name',
        'Last Name',
        'Gender',
        'Date of Birth',
        'Age',
        'Shirt Size',
        'School',
        'Medical Issues',
        'Medical Insurance',
        'Family ID',
        'Parent/Guardian',
        'Created Date'
    );
    
    $data = array();
    
    foreach ($children as $child) {
        $first_name = get_post_meta($child->ID, 'first_name', true);
        $last_name = get_post_meta($child->ID, 'last_name', true);
        $gender = get_post_meta($child->ID, 'gender', true);
        $dob = get_post_meta($child->ID, 'dob', true);
        $shirt_size = get_post_meta($child->ID, 'shirt_size', true);
        $school = get_post_meta($child->ID, 'school', true);
        $medical_issues = get_post_meta($child->ID, 'medical_issues', true);
        $medical_insurance = get_post_meta($child->ID, 'medical_insurance', true);
        $family_id = get_post_meta($child->ID, 'family_id', true);
        
        // Calculate age
        $age = '';
        if (!empty($dob)) {
            $dob_obj = new DateTime($dob);
            $today = new DateTime();
            $age = $dob_obj->diff($today)->y;
        }
        
        // Get parent/guardian names
        $parent_name = '';
        if (!empty($family_id)) {
            $parent_first_name = get_post_meta($family_id, 'first_name', true);
            $parent_last_name = get_post_meta($family_id, 'last_name', true);
            $parent_name = $parent_first_name . ' ' . $parent_last_name;
        }
        
        $row = array(
            $child->ID,
            $first_name,
            $last_name,
            ucfirst($gender),
            !empty($dob) ? date('m/d/Y', strtotime($dob)) : '',
            $age,
            $shirt_size,
            $school,
            $medical_issues,
            ucfirst($medical_insurance),
            $family_id,
            $parent_name,
            get_the_date('m/d/Y', $child->ID)
        );
        
        $data[] = $row;
    }
    
    return array($headers, $data);
}

/**
 * Get team roster export data
 */
function srs_get_team_roster_export_data($sport_type, $season_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'srs_registrations';
    
    $query = "SELECT * FROM $table_name WHERE 1=1";
    $query_args = array();
    
    if (!empty($sport_type)) {
        $query .= " AND form_type = %s";
        $query_args[] = $sport_type;
    }
    
    if (!empty($season_id)) {
        // Get season date range
        $start_date = get_post_meta($season_id, 'start_date', true);
        $end_date = get_post_meta($season_id, 'end_date', true);
        
        if (!empty($start_date) && !empty($end_date)) {
            $query .= " AND created_at BETWEEN %s AND %s";
            $query_args[] = $start_date . ' 00:00:00';
            $query_args[] = $end_date . ' 23:59:59';
        }
    }
    
    $query .= " ORDER BY last_name ASC, first_name ASC";
    
    if (!empty($query_args)) {
        $registrations = $wpdb->get_results($wpdb->prepare($query, $query_args));
    } else {
        $registrations = $wpdb->get_results($query);
    }
    
    $headers = array(
        '#',
        'Name',
        'Gender',
        'Age',
        'Shirt Size',
        'School',
        'Medical Issues',
        'Emergency Contact',
        'Emergency Phone'
    );
    
    $data = array();
    $count = 1;
    
    foreach ($registrations as $registration) {
        $form_data = json_decode($registration->form_data, true);
        
        // Calculate age
        $dob = isset($form_data['dob']) ? $form_data['dob'] : '';
        $age = '';
        
        if (!empty($dob)) {
            $dob_obj = new DateTime($dob);
            $today = new DateTime();
            $age = $dob_obj->diff($today)->y;
        }
        
        $row = array(
            $count,
            $registration->first_name . ' ' . $registration->last_name,
            isset($form_data['gender']) ? ucfirst($form_data['gender']) : '',
            $age,
            $form_data['shirt_size'] ?? '',
            $form_data['school'] ?? '',
            $form_data['medical_issues'] ?? '',
            $form_data['emergency_contact'] ?? '',
            $form_data['emergency_phone'] ?? ''
        );
        
        $data[] = $row;
        $count++;
    }
    
    return array($headers, $data);
}

/**
 * Get financial export data
 */
function srs_get_financial_export_data($sport_type, $season_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'srs_registrations';
    
    $query = "SELECT * FROM $table_name WHERE 1=1";
    $query_args = array();
    
    if (!empty($sport_type)) {
        $query .= " AND form_type = %s";
        $query_args[] = $sport_type;
    }
    
    if (!empty($season_id)) {
        // Get season date range
        $start_date = get_post_meta($season_id, 'start_date', true);
        $end_date = get_post_meta($season_id, 'end_date', true);
        
        if (!empty($start_date) && !empty($end_date)) {
            $query .= " AND created_at BETWEEN %s AND %s";
            $query_args[] = $start_date . ' 00:00:00';
            $query_args[] = $end_date . ' 23:59:59';
        }
    }
    
    $query .= " ORDER BY created_at DESC";
    
    if (!empty($query_args)) {
        $registrations = $wpdb->get_results($wpdb->prepare($query, $query_args));
    } else {
        $registrations = $wpdb->get_results($query);
    }
    
    $headers = array(
        'ID',
        'Date',
        'Child Name',
        'Parent/Guardian',
        'Sport',
        'Standard Fee',
        'Discount',
        'Final Fee',
        'Payment Status',
        'Payment ID'
    );
    
    $data = array();
    
    foreach ($registrations as $registration) {
        $form_data = json_decode($registration->form_data, true);
        
        // Get standard fee for the sport
        $standard_fee = 0;
        $sport_settings = get_option('srs_' . $registration->form_type . '_settings', array());
        if (!empty($sport_settings['price'])) {
            $standard_fee = floatval($sport_settings['price']);
        }
        
        // Calculate discount
        $final_fee = $registration->payment_amount ? floatval($registration->payment_amount) : 0;
        $discount = $standard_fee - $final_fee;
        
        $row = array(
            $registration->id,
            date('m/d/Y', strtotime($registration->created_at)),
            $registration->first_name . ' ' . $registration->last_name,
            isset($form_data['parent_first_name']) && isset($form_data['parent_last_name']) ? $form_data['parent_first_name'] . ' ' . $form_data['parent_last_name'] : '',
            ucfirst($registration->form_type),
            '$' . number_format($standard_fee, 2),
            $discount > 0 ? '$' . number_format($discount, 2) : '-',
            '$' . number_format($final_fee, 2),
            ucfirst($registration->payment_status),
            $registration->payment_id ? $registration->payment_id : '-'
        );
        
        $data[] = $row;
    }
    
    return array($headers, $data);
}

/**
 * Generate CSV export
 */
function srs_generate_csv_export($headers, $data, $export_type) {
    // Set headers for file download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $export_type . '-export-' . date('Y-m-d') . '.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add headers
    fputcsv($output, $headers);
    
    // Add data rows
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    // Close output stream
    fclose($output);
    exit;
}

/**
 * Generate Excel export
 */
function srs_generate_excel_export($headers, $data, $export_type) {
    // Check if PHPExcel library is available
    if (!class_exists('PHPExcel') && file_exists(SRS_PLUGIN_DIR . 'vendor/phpoffice/phpexcel/Classes/PHPExcel.php')) {
        require_once SRS_PLUGIN_DIR . 'vendor/phpoffice/phpexcel/Classes/PHPExcel.php';
    }
    
    if (!class_exists('PHPExcel')) {
        // Fallback to CSV if PHPExcel is not available
        srs_generate_csv_export($headers, $data, $export_type);
        return;
    }
    
    // Create new PHPExcel object
    $objPHPExcel = new PHPExcel();
    
    // Set document properties
    $objPHPExcel->getProperties()->setCreator('Sports Registration System')
        ->setLastModifiedBy('Sports Registration System')
        ->setTitle($export_type . ' Export')
        ->setSubject($export_type . ' Export')
        ->setDescription('Export of ' . $export_type . ' data.');
    
    // Add headers
    $column = 'A';
    foreach ($headers as $header) {
        $objPHPExcel->getActiveSheet()->setCellValue($column . '1', $header);
        $column++;
    }
    
    // Add data
    $row = 2;
    foreach ($data as $data_row) {
        $column = 'A';
        foreach ($data_row as $cell_value) {
            $objPHPExcel->getActiveSheet()->setCellValue($column . $row, $cell_value);
            $column++;
        }
        $row++;
    }
    
    // Set column widths to auto
    $max_column = $objPHPExcel->getActiveSheet()->getHighestColumn();
    for ($col = 'A'; $col <= $max_column; $col++) {
        $objPHPExcel->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Set headers for file download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $export_type . '-export-' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    
    // Save to php://output
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save('php://output');
    exit;
}

/**
 * Generate PDF export
 */
function srs_generate_pdf_export($headers, $data, $export_type) {
    // Check if TCPDF library is available
    if (!class_exists('TCPDF') && file_exists(SRS_PLUGIN_DIR . 'vendor/tecnickcom/tcpdf/tcpdf.php')) {
        require_once SRS_PLUGIN_DIR . 'vendor/tecnickcom/tcpdf/tcpdf.php';
    }
    
    if (!class_exists('TCPDF')) {
        // Fallback to CSV if TCPDF is not available
        srs_generate_csv_export($headers, $data, $export_type);
        return;
    }
    
    // Create new PDF document
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Sports Registration System');
    $pdf->SetAuthor('Sports Registration System');
    $pdf->SetTitle($export_type . ' Export');
    $pdf->SetSubject($export_type . ' Export');
    
    // Set default header data
    $pdf->SetHeaderData('', 0, $export_type . ' Export', date('Y-m-d'));
    
    // Set margins
    $pdf->SetMargins(10, 20, 10);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(true, 15);
    
    // Add a page
    $pdf->AddPage();
    
    // Create the table
    $html = '<table border="1" cellpadding="3" cellspacing="0">';
    
    // Add headers
    $html .= '<tr style="background-color: #f0f0f0; font-weight: bold;">';
    foreach ($headers as $header) {
        $html .= '<th>' . htmlspecialchars($header) . '</th>';
    }
    $html .= '</tr>';
    
    // Add data
    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($row as $cell) {
            $html .= '<td>' . htmlspecialchars($cell) . '</td>';
        }
        $html .= '</tr>';
    }
    
    $html .= '</table>';
    
    // Output the HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Close and output PDF document
    $pdf->Output($export_type . '-export-' . date('Y-m-d') . '.pdf', 'D');
    exit;
}

/**
 * Add CSS for reports page
 */
function srs_add_reports_css() {
    $screen = get_current_screen();
    
    if ($screen && $screen->id === 'sports-registration_page_sports-registration-reports') {
        ?>
        <style>
            .srs-summary-stats {
                margin-bottom: 20px;
            }
            
            .srs-summary-row {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
                margin-bottom: 20px;
            }
            
            .srs-summary-card {
                background-color: #fff;
                border: 1px solid #e0e0e0;
                border-radius: 4px;
                padding: 15px;
                text-align: center;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            }
            
            .srs-summary-value {
                font-size: 24px;
                font-weight: 600;
                color: #0073aa;
                margin-bottom: 5px;
            }
            
            .srs-summary-label {
                color: #555;
                font-size: 14px;
            }
            
            .srs-sports-breakdown {
                margin-top: 20px;
            }
            
            .srs-sports-breakdown h3 {
                margin-bottom: 15px;
            }
            
            .srs-sport-bars {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            
            .srs-sport-bar-wrapper {
                display: flex;
                align-items: center;
            }
            
            .srs-sport-label {
                width: 120px;
                font-weight: 600;
            }
            
            .srs-sport-bar-container {
                flex: 1;
                height: 24px;
                background-color: #f0f0f0;
                border-radius: 3px;
                overflow: hidden;
                position: relative;
            }
            
            .srs-sport-bar {
                height: 100%;
                background-color: #0073aa;
                transition: width 0.5s ease;
                display: flex;
                align-items: center;
                padding: 0 10px;
            }
            
            .srs-sport-count {
                color: #fff;
                font-weight: 600;
                z-index: 1;
                white-space: nowrap;
            }
            
            @media (max-width: 782px) {
                .srs-sport-bar-wrapper {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 5px;
                }
                
                .srs-sport-label {
                    width: 100%;
                    margin-bottom: 5px;
                }
            }
        </style>
        <?php
    }
}
add_action('admin_head', 'srs_add_reports_css');

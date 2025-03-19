<?php
/**
 * Registrations List Table
 */
class SRS_Registrations_List_Table extends WP_List_Table {
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => 'registration',
            'plural'   => 'registrations',
            'ajax'     => false,
        ));
    }
    
    /**
     * Get all registrations
     */
    private function get_registrations($per_page = 20, $page_number = 1, $search = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'srs_registrations';
        
        $sql = "SELECT * FROM $table_name";
        
        // Handle search
        if (!empty($search)) {
            $sql .= $wpdb->prepare(
                " WHERE first_name LIKE %s OR last_name LIKE %s",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }
        
        // Handle filtering
        $filter_form_type = isset($_REQUEST['form_type']) ? sanitize_text_field($_REQUEST['form_type']) : '';
        $filter_payment_status = isset($_REQUEST['payment_status']) ? sanitize_text_field($_REQUEST['payment_status']) : '';
        
        if (!empty($filter_form_type)) {
            $sql .= empty($search) ? ' WHERE' : ' AND';
            $sql .= $wpdb->prepare(" form_type = %s", $filter_form_type);
        }
        
        if (!empty($filter_payment_status)) {
            $sql .= empty($search) && empty($filter_form_type) ? ' WHERE' : ' AND';
            $sql .= $wpdb->prepare(" payment_status = %s", $filter_payment_status);
        }
        
        // Handle sorting
        $orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'created_at';
        $order = isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC';
        
        // Ensure the orderby value is allowed
        $allowed_orderby = array('id', 'first_name', 'last_name', 'form_type', 'payment_status', 'created_at');
        if (!in_array($orderby, $allowed_orderby)) {
            $orderby = 'created_at';
        }
        
        // Ensure the order is either ASC or DESC
        if (!in_array(strtoupper($order), array('ASC', 'DESC'))) {
            $order = 'DESC';
        }
        
        $sql .= " ORDER BY $orderby $order";
        
        // Handle pagination
        $sql .= $wpdb->prepare(" LIMIT %d OFFSET %d", $per_page, ($page_number - 1) * $per_page);
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        return $results;
    }
    
    /**
     * Get total number of registrations
     */
    private function get_total_registrations($search = '') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'srs_registrations';
        
        $sql = "SELECT COUNT(*) FROM $table_name";
        
        // Handle search
        if (!empty($search)) {
            $sql .= $wpdb->prepare(
                " WHERE first_name LIKE %s OR last_name LIKE %s",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }
        
        // Handle filtering
        $filter_form_type = isset($_REQUEST['form_type']) ? sanitize_text_field($_REQUEST['form_type']) : '';
        $filter_payment_status = isset($_REQUEST['payment_status']) ? sanitize_text_field($_REQUEST['payment_status']) : '';
        
        if (!empty($filter_form_type)) {
            $sql .= empty($search) ? ' WHERE' : ' AND';
            $sql .= $wpdb->prepare(" form_type = %s", $filter_form_type);
        }
        
        if (!empty($filter_payment_status)) {
            $sql .= empty($search) && empty($filter_form_type) ? ' WHERE' : ' AND';
            $sql .= $wpdb->prepare(" payment_status = %s", $filter_payment_status);
        }
        
        return $wpdb->get_var($sql);
    }
    
    /**
     * Get registration by ID
     */
    private function get_registration($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'srs_registrations';
        
        $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id);
        
        return $wpdb->get_row($sql, ARRAY_A);
    }
    
    /**
     * Delete registration by ID
     */
    private function delete_registration($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'srs_registrations';
        
        return $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d')
        );
    }
    
    /**
     * Process bulk actions
     */
    public function process_bulk_action() {
        // Handle single delete
        if ('delete' === $this->current_action()) {
            $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : '';
            
            if (!wp_verify_nonce($nonce, 'srs_delete_registration')) {
                die('Security check failed');
            }
            
            $this->delete_registration(absint($_REQUEST['registration']));
            
            wp_redirect(add_query_arg(array(
                'page' => 'sports-registration-list',
                'deleted' => '1',
            ), admin_url('admin.php')));
            exit;
        }
        
        // Handle bulk delete
        if ('bulk-delete' === $this->current_action()) {
            $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : '';
            
            if (!wp_verify_nonce($nonce, 'bulk-' . $this->_args['plural'])) {
                die('Security check failed');
            }
            
            $delete_ids = isset($_REQUEST['registrations']) ? $_REQUEST['registrations'] : array();
            
            if (is_array($delete_ids) && !empty($delete_ids)) {
                foreach ($delete_ids as $id) {
                    $this->delete_registration(absint($id));
                }
            }
            
            wp_redirect(add_query_arg(array(
                'page' => 'sports-registration-list',
                'deleted' => count($delete_ids),
            ), admin_url('admin.php')));
            exit;
        }
    }
    
    /**
     * Define columns
     */
    public function get_columns() {
        $columns = array(
            'cb'             => '<input type="checkbox" />',
            'name'           => __('Name', 'sports-registration'),
            'form_type'      => __('Sport', 'sports-registration'),
            'payment_status' => __('Payment', 'sports-registration'),
            'created_at'     => __('Date', 'sports-registration'),
            'actions'        => __('Actions', 'sports-registration'),
        );
        
        return $columns;
    }
    
    /**
     * Define sortable columns
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'name'           => array('first_name', false),
            'form_type'      => array('form_type', false),
            'payment_status' => array('payment_status', false),
            'created_at'     => array('created_at', true),
        );
        
        return $sortable_columns;
    }
    
    /**
     * Define bulk actions
     */
    public function get_bulk_actions() {
        $actions = array(
            'bulk-delete' => __('Delete', 'sports-registration'),
        );
        
        return $actions;
    }
    
    /**
     * Add extra filter options
     */
    public function extra_tablenav($which) {
        if ('top' !== $which) {
            return;
        }
        
        $form_type = isset($_REQUEST['form_type']) ? sanitize_text_field($_REQUEST['form_type']) : '';
        $payment_status = isset($_REQUEST['payment_status']) ? sanitize_text_field($_REQUEST['payment_status']) : '';
        ?>
        <div class="alignleft actions">
            <select name="form_type">
                <option value=""><?php _e('All Sports', 'sports-registration'); ?></option>
                <option value="basketball" <?php selected($form_type, 'basketball'); ?>><?php _e('Basketball', 'sports-registration'); ?></option>
                <option value="soccer" <?php selected($form_type, 'soccer'); ?>><?php _e('Soccer', 'sports-registration'); ?></option>
                <option value="cheerleading" <?php selected($form_type, 'cheerleading'); ?>><?php _e('Cheerleading', 'sports-registration'); ?></option>
                <option value="volleyball" <?php selected($form_type, 'volleyball'); ?>><?php _e('Volleyball', 'sports-registration'); ?></option>
            </select>
            
            <select name="payment_status">
                <option value=""><?php _e('All Payments', 'sports-registration'); ?></option>
                <option value="paid" <?php selected($payment_status, 'paid'); ?>><?php _e('Paid', 'sports-registration'); ?></option>
                <option value="pending" <?php selected($payment_status, 'pending'); ?>><?php _e('Pending', 'sports-registration'); ?></option>
                <option value="none" <?php selected($payment_status, 'none'); ?>><?php _e('None', 'sports-registration'); ?></option>
            </select>
            
            <?php submit_button(__('Filter', 'sports-registration'), '', 'filter_action', false); ?>
        </div>
        <?php
    }
    
    /**
     * Checkbox column
     */
    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="registrations[]" value="%s" />',
            $item['id']
        );
    }
    
    /**
     * Name column
     */
    public function column_name($item) {
        $view_url = add_query_arg(array(
            'page' => 'sports-registration-list',
            'action' => 'view',
            'registration' => $item['id'],
        ), admin_url('admin.php'));
        
        $delete_url = wp_nonce_url(
            add_query_arg(array(
                'page' => 'sports-registration-list',
                'action' => 'delete',
                'registration' => $item['id'],
            ), admin_url('admin.php')),
            'srs_delete_registration'
        );
        
        $name = sprintf('%s %s', $item['first_name'], $item['last_name']);
        
        $actions = array(
            'view' => sprintf('<a href="%s">%s</a>', $view_url, __('View', 'sports-registration')),
            'delete' => sprintf('<a href="%s" onclick="return confirm(\'%s\');">%s</a>', $delete_url, __('Are you sure you want to delete this registration?', 'sports-registration'), __('Delete', 'sports-registration')),
        );
        
        return $name . $this->row_actions($actions);
    }
    
    /**
     * Form type column
     */
    public function column_form_type($item) {
        return ucfirst($item['form_type']);
    }
    
    /**
     * Payment status column
     */
    public function column_payment_status($item) {
        switch ($item['payment_status']) {
            case 'paid':
                return sprintf('<span class="srs-status-paid">%s</span>', __('Paid', 'sports-registration'));
            case 'pending':
                return sprintf('<span class="srs-status-pending">%s</span>', __('Pending', 'sports-registration'));
            default:
                return sprintf('<span class="srs-status-none">%s</span>', __('None', 'sports-registration'));
        }
    }
    
    /**
     * Created at column
     */
    public function column_created_at($item) {
        return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item['created_at']));
    }
    
    /**
     * Actions column
     */
    public function column_actions($item) {
        $view_url = add_query_arg(array(
            'page' => 'sports-registration-list',
            'action' => 'view',
            'registration' => $item['id'],
        ), admin_url('admin.php'));
        
        $export_url = wp_nonce_url(
            add_query_arg(array(
                'page' => 'sports-registration-list',
                'action' => 'export',
                'registration' => $item['id'],
            ), admin_url('admin.php')),
            'srs_export_registration'
        );
        
        return sprintf(
            '<a href="%s" class="button button-small">%s</a> <a href="%s" class="button button-small">%s</a>',
            $view_url,
            __('View', 'sports-registration'),
            $export_url,
            __('Export', 'sports-registration')
        );
    }
    
    /**
     * Default column handler
     */
    public function column_default($item, $column_name) {
        return isset($item[$column_name]) ? $item[$column_name] : '';
    }
    
    /**
     * Prepare items for display
     */
    public function prepare_items() {
        $this->process_bulk_action();
        
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->items = $this->get_registrations($per_page, $current_page, $search);
        
        $total_items = $this->get_total_registrations($search);
        
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ));
    }
    
    /**
     * Display the registration details
     */
    public function display_registration_details($id) {
        $registration = $this->get_registration($id);
        
        if (!$registration) {
            echo '<div class="notice notice-error"><p>' . __('Registration not found.', 'sports-registration') . '</p></div>';
            return;
        }
        
        $form_data = json_decode($registration['form_data'], true);
        ?>
        <div class="wrap">
            <h1><?php printf(__('Registration Details: %s %s', 'sports-registration'), $registration['first_name'], $registration['last_name']); ?></h1>
            
            <div class="srs-registration-details">
                <div class="srs-registration-header">
                    <div class="srs-registration-meta">
                        <div class="srs-meta-box">
                            <span class="srs-meta-label"><?php _e('Sport:', 'sports-registration'); ?></span>
                            <span class="srs-meta-value"><?php echo ucfirst($registration['form_type']); ?></span>
                        </div>
                        
                        <div class="srs-meta-box">
                            <span class="srs-meta-label"><?php _e('Date:', 'sports-registration'); ?></span>
                            <span class="srs-meta-value"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($registration['created_at'])); ?></span>
                        </div>
                        
                        <div class="srs-meta-box">
                            <span class="srs-meta-label"><?php _e('Payment:', 'sports-registration'); ?></span>
                            <span class="srs-meta-value">
                                <?php
                                switch ($registration['payment_status']) {
                                    case 'paid':
                                        echo '<span class="srs-status-paid">' . __('Paid', 'sports-registration') . '</span>';
                                        break;
                                    case 'pending':
                                        echo '<span class="srs-status-pending">' . __('Pending', 'sports-registration') . '</span>';
                                        break;
                                    default:
                                        echo '<span class="srs-status-none">' . __('None', 'sports-registration') . '</span>';
                                        break;
                                }
                                ?>
                            </span>
                        </div>
                        
                        <?php if (!empty($registration['payment_id'])): ?>
                            <div class="srs-meta-box">
                                <span class="srs-meta-label"><?php _e('Payment ID:', 'sports-registration'); ?></span>
                                <span class="srs-meta-value"><?php echo esc_html($registration['payment_id']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($registration['payment_amount']) && $registration['payment_amount'] > 0): ?>
                            <div class="srs-meta-box">
                                <span class="srs-meta-label"><?php _e('Amount:', 'sports-registration'); ?></span>
                                <span class="srs-meta-value">$<?php echo number_format($registration['payment_amount'], 2); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="srs-registration-actions">
                        <a href="<?php echo add_query_arg(array('page' => 'sports-registration-list'), admin_url('admin.php')); ?>" class="button"><?php _e('Back to List', 'sports-registration'); ?></a>
                        
                        <?php
                        $export_url = wp_nonce_url(
                            add_query_arg(array(
                                'page' => 'sports-registration-list',
                                'action' => 'export',
                                'registration' => $registration['id'],
                            ), admin_url('admin.php')),
                            'srs_export_registration'
                        );
                        
                        $delete_url = wp_nonce_url(
                            add_query_arg(array(
                                'page' => 'sports-registration-list',
                                'action' => 'delete',
                                'registration' => $registration['id'],
                            ), admin_url('admin.php')),
                            'srs_delete_registration'
                        );
                        ?>
                        
                        <a href="<?php echo esc_url($export_url); ?>" class="button"><?php _e('Export', 'sports-registration'); ?></a>
                        <a href="<?php echo esc_url($delete_url); ?>" class="button button-link-delete" onclick="return confirm('<?php _e('Are you sure you want to delete this registration?', 'sports-registration'); ?>');"><?php _e('Delete', 'sports-registration'); ?></a>
                    </div>
                </div>
                
                <div class="srs-registration-data">
                    <h2><?php _e('Personal Information', 'sports-registration'); ?></h2>
                    
                    <table class="widefat fixed striped">
                        <tbody>
                            <tr>
                                <th width="200"><?php _e('First Name', 'sports-registration'); ?></th>
                                <td><?php echo esc_html($form_data['first_name'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Last Name', 'sports-registration'); ?></th>
                                <td><?php echo esc_html($form_data['last_name'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Gender', 'sports-registration'); ?></th>
                                <td><?php echo esc_html(ucfirst($form_data['gender'] ?? '')); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Shirt Size', 'sports-registration'); ?></th>
                                <td><?php echo esc_html($form_data['shirt_size'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Date of Birth', 'sports-registration'); ?></th>
                                <td>
                                    <?php
                                    if (!empty($form_data['dob'])) {
                                        echo esc_html(date_i18n(get_option('date_format'), strtotime($form_data['dob'])));
                                        
                                        // Calculate age as of August 1 of last year
                                        $dob = new DateTime($form_data['dob']);
                                        $today = new DateTime();
                                        $last_year = $today->format('Y') - 1;
                                        $ref_date = new DateTime($last_year . '-08-01');
                                        
                                        $age = $ref_date->diff($dob)->y;
                                        
                                        echo ' <span class="srs-age-note">(' . sprintf(__('Age as of August 1, %d: %d', 'sports-registration'), $last_year, $age) . ')</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h2><?php _e('Contact Information', 'sports-registration'); ?></h2>
                    
                    <table class="widefat fixed striped">
                        <tbody>
                            <tr>
                                <th width="200"><?php _e('Address', 'sports-registration'); ?></th>
                                <td><?php echo esc_html($form_data['address'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('City', 'sports-registration'); ?></th>
                                <td><?php echo esc_html($form_data['city'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('State', 'sports-registration'); ?></th>
                                <td><?php echo esc_html($form_data['state'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Zip Code', 'sports-registration'); ?></th>
                                <td><?php echo esc_html($form_data['zip'] ?? ''); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Phone', 'sports-registration'); ?></th>
                                <td><?php echo esc_html($form_data['phone'] ?? ''); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h2><?php _e('Additional Information', 'sports-registration'); ?></h2>
                    
                    <table class="widefat fixed striped">
                        <tbody>
                            <tr>
                                <th width="200"><?php _e('School', 'sports-registration'); ?></th>
                                <td><?php echo esc_html($form_data['school'] ?? ''); ?></td>
                            </tr>
                            
                            <?php if (isset($form_data['medical_issues'])): ?>
                                <tr>
                                    <th><?php _e('Medical Issues', 'sports-registration'); ?></th>
                                    <td><?php echo nl2br(esc_html($form_data['medical_issues'])); ?></td>
                                </tr>
                            <?php endif; ?>
                            
                            <?php if (isset($form_data['medical_insurance'])): ?>
                                <tr>
                                    <th><?php _e('Medical Insurance', 'sports-registration'); ?></th>
                                    <td><?php echo esc_html(ucfirst($form_data['medical_insurance'])); ?></td>
                                </tr>
                            <?php endif; ?>
                            
                            <?php if (isset($form_data['siblings'])): ?>
                                <tr>
                                    <th><?php _e('Siblings', 'sports-registration'); ?></th>
                                    <td><?php echo nl2br(esc_html($form_data['siblings'])); ?></td>
                                </tr>
                            <?php endif; ?>
                            
                            <tr>
                                <th><?php _e('Emergency Contact', 'sports-registration'); ?></th>
                                <td><?php echo esc_html($form_data['emergency_contact'] ?? ''); ?></td>
                            </tr>
                            
                            <tr>
                                <th><?php _e('Emergency Phone', 'sports-registration'); ?></th>
                                <td><?php echo esc_html($form_data['emergency_phone'] ?? ''); ?></td>
                            </tr>
                            
                            <?php if (isset($form_data['social_media_waiver'])): ?>
                                <tr>
                                    <th><?php _e('Social Media Waiver', 'sports-registration'); ?></th>
                                    <td><?php echo esc_html(ucfirst($form_data['social_media_waiver'])); ?></td>
                                </tr>
                            <?php endif; ?>
                            
                            <?php if (isset($form_data['disclosure'])): ?>
                                <tr>
                                    <th><?php _e('Disclosure', 'sports-registration'); ?></th>
                                    <td><?php echo esc_html($form_data['disclosure'] == 1 ? 'Accepted' : 'Not accepted'); ?></td>
                                </tr>
                            <?php endif; ?>
                            
                            <?php if (isset($form_data['signature']) && !empty($form_data['signature'])): ?>
                                <tr>
                                    <th><?php _e('Signature', 'sports-registration'); ?></th>
                                    <td>
                                        <img src="<?php echo esc_attr($form_data['signature']); ?>" alt="<?php _e('Signature', 'sports-registration'); ?>" style="max-width: 400px; border: 1px solid #ddd;" />
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}

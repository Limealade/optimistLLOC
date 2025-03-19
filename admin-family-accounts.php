<?php
/**
 * Admin views for managing family accounts
 */

/**
 * Add admin menu pages for family accounts
 */
function srs_add_family_accounts_admin_menu() {
    add_submenu_page(
        'sports-registration',
        'Family Accounts',
        'Family Accounts',
        'manage_options',
        'sports-registration-families',
        'srs_display_family_accounts_page'
    );
    
    add_submenu_page(
        'sports-registration',
        'Child Profiles',
        'Child Profiles',
        'manage_options',
        'sports-registration-children',
        'srs_display_child_profiles_page'
    );
    
    add_submenu_page(
        'sports-registration',
        'Registration Seasons',
        'Seasons',
        'manage_options',
        'sports-registration-seasons',
        'srs_display_seasons_page'
    );
}
add_action('admin_menu', 'srs_add_family_accounts_admin_menu');

/**
 * Display family accounts page
 */
function srs_display_family_accounts_page() {
    // Initialize family accounts list table
    if (!class_exists('WP_List_Table')) {
        require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
    }
    
    require_once SRS_PLUGIN_DIR . 'admin/class-srs-family-accounts-list-table.php';
    
    $family_accounts_table = new SRS_Family_Accounts_List_Table();
    $family_accounts_table->prepare_items();
    
    // Display view
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Family Accounts</h1>
        
        <?php if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['family'])): ?>
            <?php $family_id = intval($_GET['family']); ?>
            <?php srs_display_family_details($family_id); ?>
        <?php else: ?>
            <form method="post">
                <?php
                $family_accounts_table->search_box('Search Families', 'search_family');
                $family_accounts_table->display();
                ?>
            </form>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Display family details
 */
function srs_display_family_details($family_id) {
    $family = get_post($family_id);
    
    if (!$family || $family->post_type !== 'srs_family') {
        echo '<div class="notice notice-error"><p>Family account not found.</p></div>';
        return;
    }
    
    $first_name = get_post_meta($family_id, 'first_name', true);
    $last_name = get_post_meta($family_id, 'last_name', true);
    $email = get_post_meta($family_id, 'email', true);
    $phone = get_post_meta($family_id, 'phone', true);
    $address = get_post_meta($family_id, 'address', true);
    $city = get_post_meta($family_id, 'city', true);
    $state = get_post_meta($family_id, 'state', true);
    $zip = get_post_meta($family_id, 'zip', true);
    $children_ids = get_post_meta($family_id, 'children', true);
    
    if (empty($children_ids)) {
        $children_ids = array();
    }
    
    ?>
    <div class="srs-admin-back-link">
        <a href="<?php echo admin_url('admin.php?page=sports-registration-families'); ?>" class="button">← Back to Family Accounts</a>
    </div>
    
    <div class="srs-admin-section srs-family-details">
        <h2><?php echo esc_html($first_name . ' ' . $last_name); ?> Family</h2>
        
        <div class="srs-admin-columns">
            <div class="srs-admin-column">
                <h3>Contact Information</h3>
                <table class="widefat fixed striped">
                    <tbody>
                        <tr>
                            <th width="150">Parents</th>
                            <td><?php echo esc_html($first_name . ' ' . $last_name); ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?php echo esc_html($email); ?></td>
                        </tr>
                        <tr>
                            <th>Phone</th>
                            <td><?php echo esc_html($phone); ?></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td>
                                <?php echo esc_html($address); ?><br>
                                <?php echo esc_html($city . ', ' . $state . ' ' . $zip); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="srs-admin-column">
                <h3>Account Information</h3>
                <table class="widefat fixed striped">
                    <tbody>
                        <tr>
                            <th width="150">Account ID</th>
                            <td><?php echo esc_html($family_id); ?></td>
                        </tr>
                        <tr>
                            <th>Created</th>
                            <td><?php echo get_the_date(get_option('date_format') . ' ' . get_option('time_format'), $family_id); ?></td>
                        </tr>
                        <tr>
                            <th>Last Updated</th>
                            <td><?php echo get_the_modified_date(get_option('date_format') . ' ' . get_option('time_format'), $family_id); ?></td>
                        </tr>
                        <tr>
                            <th>Children</th>
                            <td><?php echo count($children_ids); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="srs-admin-section">
            <h3>Children</h3>
            
            <?php if (empty($children_ids)): ?>
                <p>No children added to this family account.</p>
            <?php else: ?>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Date of Birth</th>
                            <th>Age</th>
                            <th>School</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($children_ids as $child_id): ?>
                            <?php
                            $child = get_post($child_id);
                            
                            if (!$child || $child->post_type !== 'srs_child') {
                                continue;
                            }
                            
                            $child_first_name = get_post_meta($child_id, 'first_name', true);
                            $child_last_name = get_post_meta($child_id, 'last_name', true);
                            $child_gender = get_post_meta($child_id, 'gender', true);
                            $child_dob = get_post_meta($child_id, 'dob', true);
                            $child_school = get_post_meta($child_id, 'school', true);
                            
                            // Calculate age
                            $dob = new DateTime($child_dob);
                            $today = new DateTime();
                            $age = $dob->diff($today)->y;
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=sports-registration-children&action=view&child=' . $child_id); ?>">
                                        <?php echo esc_html($child_first_name . ' ' . $child_last_name); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html(ucfirst($child_gender)); ?></td>
                                <td><?php echo esc_html(date('m/d/Y', strtotime($child_dob))); ?></td>
                                <td><?php echo esc_html($age); ?></td>
                                <td><?php echo esc_html($child_school); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=sports-registration-children&action=view&child=' . $child_id); ?>" class="button button-small">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="srs-admin-section">
            <h3>Registrations</h3>
            
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'srs_registrations';
            
            $registrations = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table_name WHERE form_data LIKE %s ORDER BY created_at DESC",
                    '%"family_id":"' . $family_id . '"%'
                )
            );
            ?>
            
            <?php if (empty($registrations)): ?>
                <p>No registrations found for this family.</p>
            <?php else: ?>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Child</th>
                            <th>Sport</th>
                            <th>Registration Date</th>
                            <th>Payment Status</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $registration): ?>
                            <?php
                            $form_data = json_decode($registration->form_data, true);
                            $child_id = $form_data['child_id'] ?? 0;
                            $child_first_name = '';
                            $child_last_name = '';
                            
                            if ($child_id) {
                                $child_first_name = get_post_meta($child_id, 'first_name', true);
                                $child_last_name = get_post_meta($child_id, 'last_name', true);
                            }
                            ?>
                            <tr>
                                <td>
                                    <?php if ($child_id): ?>
                                        <a href="<?php echo admin_url('admin.php?page=sports-registration-children&action=view&child=' . $child_id); ?>">
                                            <?php echo esc_html($child_first_name . ' ' . $child_last_name); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo esc_html($registration->first_name . ' ' . $registration->last_name); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(ucfirst($registration->form_type)); ?></td>
                                <td><?php echo esc_html(date('m/d/Y', strtotime($registration->created_at))); ?></td>
                                <td>
                                    <?php
                                    switch ($registration->payment_status) {
                                        case 'paid':
                                            echo '<span class="srs-status srs-status-paid">Paid</span>';
                                            break;
                                        case 'pending':
                                            echo '<span class="srs-status srs-status-pending">Pending</span>';
                                            break;
                                        default:
                                            echo '<span class="srs-status srs-status-none">None</span>';
                                            break;
                                    }
                                    ?>
                                </td>
                                <td><?php echo !empty($registration->payment_amount) ? '$' . number_format($registration->payment_amount, 2) : '-'; ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=sports-registration-list&action=view&registration=' . $registration->id); ?>" class="button button-small">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Display child profiles page
 */
function srs_display_child_profiles_page() {
    // Initialize child profiles list table
    if (!class_exists('WP_List_Table')) {
        require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
    }
    
    require_once SRS_PLUGIN_DIR . 'admin/class-srs-child-profiles-list-table.php';
    
    $child_profiles_table = new SRS_Child_Profiles_List_Table();
    $child_profiles_table->prepare_items();
    
    // Display view
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Child Profiles</h1>
        
        <?php if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['child'])): ?>
            <?php $child_id = intval($_GET['child']); ?>
            <?php srs_display_child_details($child_id); ?>
        <?php else: ?>
            <form method="post">
                <?php
                $child_profiles_table->search_box('Search Children', 'search_child');
                $child_profiles_table->display();
                ?>
            </form>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Display child details
 */
function srs_display_child_details($child_id) {
    $child = get_post($child_id);
    
    if (!$child || $child->post_type !== 'srs_child') {
        echo '<div class="notice notice-error"><p>Child profile not found.</p></div>';
        return;
    }
    
    $first_name = get_post_meta($child_id, 'first_name', true);
    $last_name = get_post_meta($child_id, 'last_name', true);
    $gender = get_post_meta($child_id, 'gender', true);
    $dob = get_post_meta($child_id, 'dob', true);
    $shirt_size = get_post_meta($child_id, 'shirt_size', true);
    $school = get_post_meta($child_id, 'school', true);
    $medical_issues = get_post_meta($child_id, 'medical_issues', true);
    $medical_insurance = get_post_meta($child_id, 'medical_insurance', true);
    $family_id = get_post_meta($child_id, 'family_id', true);
    
    // Calculate age
    $dob_obj = new DateTime($dob);
    $today = new DateTime();
    $age = $dob_obj->diff($today)->y;
    
    // Get family information
    $family = get_post($family_id);
    $family_first_name = get_post_meta($family_id, 'first_name', true);
    $family_last_name = get_post_meta($family_id, 'last_name', true);
    
    ?>
    <div class="srs-admin-back-link">
        <a href="<?php echo admin_url('admin.php?page=sports-registration-children'); ?>" class="button">← Back to Child Profiles</a>
    </div>
    
    <div class="srs-admin-section srs-child-details">
        <h2><?php echo esc_html($first_name . ' ' . $last_name); ?></h2>
        
        <div class="srs-admin-columns">
            <div class="srs-admin-column">
                <h3>Personal Information</h3>
                <table class="widefat fixed striped">
                    <tbody>
                        <tr>
                            <th width="150">Name</th>
                            <td><?php echo esc_html($first_name . ' ' . $last_name); ?></td>
                        </tr>
                        <tr>
                            <th>Gender</th>
                            <td><?php echo esc_html(ucfirst($gender)); ?></td>
                        </tr>
                        <tr>
                            <th>Date of Birth</th>
                            <td><?php echo esc_html(date('F j, Y', strtotime($dob))); ?></td>
                        </tr>
                        <tr>
                            <th>Age</th>
                            <td><?php echo esc_html($age); ?> years</td>
                        </tr>
                        <tr>
                            <th>Shirt Size</th>
                            <td><?php echo esc_html($shirt_size); ?></td>
                        </tr>
                        <tr>
                            <th>School</th>
                            <td><?php echo esc_html($school); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="srs-admin-column">
                <h3>Medical Information</h3>
                <table class="widefat fixed striped">
                    <tbody>
                        <tr>
                            <th width="150">Medical Issues</th>
                            <td><?php echo esc_html($medical_issues); ?></td>
                        </tr>
                        <tr>
                            <th>Medical Insurance</th>
                            <td><?php echo esc_html(ucfirst($medical_insurance)); ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <h3>Family Information</h3>
                <table class="widefat fixed striped">
                    <tbody>
                        <tr>
                            <th width="150">Family</th>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=sports-registration-families&action=view&family=' . $family_id); ?>">
                                    <?php echo esc_html($family_first_name . ' ' . $family_last_name); ?> Family
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="srs-admin-section">
            <h3>Registration History</h3>
            
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'srs_registrations';
            
            $registrations = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table_name WHERE form_data LIKE %s ORDER BY created_at DESC",
                    '%"child_id":"' . $child_id . '"%'
                )
            );
            ?>
            
            <?php if (empty($registrations)): ?>
                <p>No registrations found for this child.</p>
            <?php else: ?>
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Sport</th>
                            <th>Registration Date</th>
                            <th>Payment Status</th>
                            <th>Amount</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $registration): ?>
                            <tr>
                                <td><?php echo esc_html(ucfirst($registration->form_type)); ?></td>
                                <td><?php echo esc_html(date('m/d/Y', strtotime($registration->created_at))); ?></td>
                                <td>
                                    <?php
                                    switch ($registration->payment_status) {
                                        case 'paid':
                                            echo '<span class="srs-status srs-status-paid">Paid</span>';
                                            break;
                                        case 'pending':
                                            echo '<span class="srs-status srs-status-pending">Pending</span>';
                                            break;
                                        default:
                                            echo '<span class="srs-status srs-status-none">None</span>';
                                            break;
                                    }
                                    ?>
                                </td>
                                <td><?php echo !empty($registration->payment_amount) ? '$' . number_format($registration->payment_amount, 2) : '-'; ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=sports-registration-list&action=view&registration=' . $registration->id); ?>" class="button button-small">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Display seasons page
 */
function srs_display_seasons_page() {
    // Initialize seasons list table
    if (!class_exists('WP_List_Table')) {
        require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
    }
    
    require_once SRS_PLUGIN_DIR . 'admin/class-srs-seasons-list-table.php';
    
    $seasons_table = new SRS_Seasons_List_Table();
    $seasons_table->prepare_items();
    
    // Check for form submission
    if (isset($_POST['add_season'])) {
        srs_handle_add_season();
    }
    
    // Display view
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Registration Seasons</h1>
        <a href="#" class="page-title-action" id="srs-add-season-button">Add New Season</a>
        
        <div id="srs-add-season-form" style="display: none; margin-top: 20px;">
            <div class="srs-admin-section">
                <h2>Add New Registration Season</h2>
                
                <form method="post" action="">
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="season_name">Season Name</label></th>
                            <td><input type="text" name="season_name" id="season_name" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="start_date">Start Date</label></th>
                            <td><input type="date" name="start_date" id="start_date" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="end_date">End Date</label></th>
                            <td><input type="date" name="end_date" id="end_date" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label>Available Sports</label></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text">Available Sports</legend>
                                    <label for="sport_basketball">
                                        <input type="checkbox" name="sport_types[]" id="sport_basketball" value="basketball">
                                        Basketball
                                    </label><br>
                                    <label for="sport_soccer">
                                        <input type="checkbox" name="sport_types[]" id="sport_soccer" value="soccer">
                                        Soccer
                                    </label><br>
                                    <label for="sport_cheerleading">
                                        <input type="checkbox" name="sport_types[]" id="sport_cheerleading" value="cheerleading">
                                        Cheerleading
                                    </label><br>
                                    <label for="sport_volleyball">
                                        <input type="checkbox" name="sport_types[]" id="sport_volleyball" value="volleyball">
                                        Volleyball
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="description">Description</label></th>
                            <td><textarea name="description" id="description" class="large-text" rows="5"></textarea></td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="add_season" id="add_season" class="button button-primary" value="Add Season">
                        <button type="button" id="srs-cancel-add-season" class="button">Cancel</button>
                    </p>
                </form>
            </div>
        </div>
        
        <form method="post">
            <?php
            $seasons_table->prepare_items();
            $seasons_table->display();
            ?>
        </form>
    </div>
    
    <script>
        jQuery(document).ready(function($) {
            // Toggle add season form
            $('#srs-add-season-button').click(function(e) {
                e.preventDefault();
                $('#srs-add-season-form').slideToggle();
            });
            
            $('#srs-cancel-add-season').click(function() {
                $('#srs-add-season-form').slideUp();
            });
        });
    </script>
    <?php
}

/**
 * Handle adding a new season
 */
function srs_handle_add_season() {
    // Verify nonce
    // check_admin_referer('add_season_nonce');
    
    // Get form data
    $season_name = sanitize_text_field($_POST['season_name']);
    $start_date = sanitize_text_field($_POST['start_date']);
    $end_date = sanitize_text_field($_POST['end_date']);
    $sport_types = isset($_POST['sport_types']) ? array_map('sanitize_text_field', $_POST['sport_types']) : array();
    $description = sanitize_textarea_field($_POST['description']);
    
    // Validate required fields
    if (empty($season_name) || empty($start_date) || empty($end_date) || empty($sport_types)) {
        add_settings_error('srs_seasons', 'required_fields', 'Please fill in all required fields.', 'error');
        return;
    }
    
    // Validate dates
    $start_date_obj = new DateTime($start_date);
    $end_date_obj = new DateTime($end_date);
    
    if ($end_date_obj < $start_date_obj) {
        add_settings_error('srs_seasons', 'invalid_dates', 'End date must be after start date.', 'error');
        return;
    }
    
    // Create season
    $season_id = wp_insert_post(array(
        'post_title' => $season_name,
        'post_type' => 'srs_season',
        'post_status' => 'publish',
    ));
    
    if (is_wp_error($season_id)) {
        add_settings_error('srs_seasons', 'season_error', 'Failed to create season. Please try again.', 'error');
        return;
    }
    
    // Save season data
    update_post_meta($season_id, 'start_date', $start_date);
    update_post_meta($season_id, 'end_date', $end_date);
    update_post_meta($season_id, 'sport_types', $sport_types);
    update_post_meta($season_id, 'description', $description);
    
    add_settings_error('srs_seasons', 'season_created', 'Registration season created successfully.', 'success');
}

/**
 * Family accounts list table
 */
class SRS_Family_Accounts_List_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct(array(
            'singular' => 'family',
            'plural' => 'families',
            'ajax' => false,
        ));
    }
    
    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        
        $args = array(
            'post_type' => 'srs_family',
            'posts_per_page' => $per_page,
            'paged' => $current_page,
            'orderby' => isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'date',
            'order' => isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC',
        );
        
        if (!empty($search)) {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => 'first_name',
                    'value' => $search,
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => 'last_name',
                    'value' => $search,
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => 'email',
                    'value' => $search,
                    'compare' => 'LIKE',
                ),
            );
        }
        
        $query = new WP_Query($args);
        
        $this->items = $query->posts;
        
        $this->set_pagination_args(array(
            'total_items' => $query->found_posts,
            'per_page' => $per_page,
            'total_pages' => $query->max_num_pages,
        ));
    }
    
    public function get_columns() {
        return array(
            'name' => 'Family Name',
            'email' => 'Email',
            'phone' => 'Phone',
            'children' => 'Children',
            'registrations' => 'Registrations',
            'date' => 'Created',
        );
    }
    
    public function get_sortable_columns() {
        return array(
            'name' => array('name', false),
            'date' => array('date', true),
        );
    }
    
    public function column_name($item) {
        $first_name = get_post_meta($item->ID, 'first_name', true);
        $last_name = get_post_meta($item->ID, 'last_name', true);
        
        $name = $first_name . ' ' . $last_name;
        
        $actions = array(
            'view' => sprintf('<a href="%s">View</a>', admin_url('admin.php?page=sports-registration-families&action=view&family=' . $item->ID)),
        );
        
        return $name . $this->row_actions($actions);
    }
    
    public function column_email($item) {
        return get_post_meta($item->ID, 'email', true);
    }
    
    public function column_phone($item) {
        return get_post_meta($item->ID, 'phone', true);
    }
    
    public function column_children($item) {
        $children = get_post_meta($item->ID, 'children', true);
        
        if (empty($children)) {
            return 0;
        }
        
        return count($children);
    }
    
    public function column_registrations($item) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'srs_registrations';
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE form_data LIKE %s",
                '%"family_id":"' . $item->ID . '"%'
            )
        );
        
        return $count;
    }
    
    public function column_date($item) {
        return get_the_date(get_option('date_format'), $item->ID);
    }
    
    public function column_default($item, $column_name) {
        return '';
    }
}

/**
 * Child profiles list table
 */
class SRS_Child_Profiles_List_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct(array(
            'singular' => 'child',
            'plural' => 'children',
            'ajax' => false,
        ));
    }
    
    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        
        $args = array(
            'post_type' => 'srs_child',
            'posts_per_page' => $per_page,
            'paged' => $current_page,
            'orderby' => isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'date',
            'order' => isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC',
        );
        
        if (!empty($search)) {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => 'first_name',
                    'value' => $search,
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => 'last_name',
                    'value' => $search,
                    'compare' => 'LIKE',
                ),
            );
        }
        
        $query = new WP_Query($args);
        
        $this->items = $query->posts;
        
        $this->set_pagination_args(array(
            'total_items' => $query->found_posts,
            'per_page' => $per_page,
            'total_pages' => $query->max_num_pages,
        ));
    }
    
    public function get_columns() {
        return array(
            'name' => 'Name',
            'gender' => 'Gender',
            'age' => 'Age',
            'family' => 'Family',
            'registrations' => 'Registrations',
            'date' => 'Created',
        );
    }
    
    public function get_sortable_columns() {
        return array(
            'name' => array('name', false),
            'date' => array('date', true),
        );
    }
    
    public function column_name($item) {
        $first_name = get_post_meta($item->ID, 'first_name', true);
        $last_name = get_post_meta($item->ID, 'last_name', true);
        
        $name = $first_name . ' ' . $last_name;
        
        $actions = array(
            'view' => sprintf('<a href="%s">View</a>', admin_url('admin.php?page=sports-registration-children&action=view&child=' . $item->ID)),
        );
        
        return $name . $this->row_actions($actions);
    }
    
    public function column_gender($item) {
        $gender = get_post_meta($item->ID, 'gender', true);
        return ucfirst($gender);
    }
    
    public function column_age($item) {
        $dob = get_post_meta($item->ID, 'dob', true);
        
        if (empty($dob)) {
            return '';
        }
        
        $dob_obj = new DateTime($dob);
        $today = new DateTime();
        $age = $dob_obj->diff($today)->y;
        
        return $age;
    }
    
    public function column_family($item) {
        $family_id = get_post_meta($item->ID, 'family_id', true);
        
        if (empty($family_id)) {
            return '';
        }
        
        $family = get_post($family_id);
        
        if (!$family || $family->post_type !== 'srs_family') {
            return '';
        }
        
        $first_name = get_post_meta($family_id, 'first_name', true);
        $last_name = get_post_meta($family_id, 'last_name', true);
        
        return sprintf(
            '<a href="%s">%s Family</a>',
            admin_url('admin.php?page=sports-registration-families&action=view&family=' . $family_id),
            $first_name . ' ' . $last_name
        );
    }
    
    public function column_registrations($item) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'srs_registrations';
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE form_data LIKE %s",
                '%"child_id":"' . $item->ID . '"%'
            )
        );
        
        return $count;
    }
    
    public function column_date($item) {
        return get_the_date(get_option('date_format'), $item->ID);
    }
    
    public function column_default($item, $column_name) {
        return '';
    }
}

/**
 * Seasons list table
 */
class SRS_Seasons_List_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct(array(
            'singular' => 'season',
            'plural' => 'seasons',
            'ajax' => false,
        ));
    }
    
    public function prepare_items() {
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $per_page = 20;
        $current_page = $this->get_pagenum();
        
        $args = array(
            'post_type' => 'srs_season',
            'posts_per_page' => $per_page,
            'paged' => $current_page,
            'orderby' => isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'date',
            'order' => isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC',
        );
        
        $query = new WP_Query($args);
        
        $this->items = $query->posts;
        
        $this->set_pagination_args(array(
            'total_items' => $query->found_posts,
            'per_page' => $per_page,
            'total_pages' => $query->max_num_pages,
        ));
    }
    
    public function get_columns() {
        return array(
            'name' => 'Season Name',
            'dates' => 'Dates',
            'sports' => 'Sports',
            'status' => 'Status',
            'date' => 'Created',
        );
    }
    
    public function get_sortable_columns() {
        return array(
            'name' => array('name', false),
            'date' => array('date', true),
        );
    }
    
    public function column_name($item) {
        $name = $item->post_title;
        
        $actions = array(
            'edit' => sprintf('<a href="%s">Edit</a>', admin_url('post.php?post=' . $item->ID . '&action=edit')),
            'delete' => sprintf('<a href="%s" onclick="return confirm(\'Are you sure you want to delete this season?\');">Delete</a>', wp_nonce_url(admin_url('post.php?post=' . $item->ID . '&action=trash'), 'trash-post_' . $item->ID)),
        );
        
        return $name . $this->row_actions($actions);
    }
    
    public function column_dates($item) {
        $start_date = get_post_meta($item->ID, 'start_date', true);
        $end_date = get_post_meta($item->ID, 'end_date', true);
        
        if (empty($start_date) || empty($end_date)) {
            return '';
        }
        
        return date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date));
    }
    
    public function column_sports($item) {
        $sport_types = get_post_meta($item->ID, 'sport_types', true);
        
        if (empty($sport_types)) {
            return '';
        }
        
        $sport_labels = array(
            'basketball' => 'Basketball',
            'soccer' => 'Soccer',
            'cheerleading' => 'Cheerleading',
            'volleyball' => 'Volleyball',
        );
        
        $sports = array();
        
        foreach ($sport_types as $sport) {
            if (isset($sport_labels[$sport])) {
                $sports[] = $sport_labels[$sport];
            }
        }
        
        return implode(', ', $sports);
    }
    
    public function column_status($item) {
        $start_date = get_post_meta($item->ID, 'start_date', true);
        $end_date = get_post_meta($item->ID, 'end_date', true);
        
        if (empty($start_date) || empty($end_date)) {
            return '';
        }
        
        $today = current_time('Y-m-d');
        
        if ($today < $start_date) {
            return '<span class="srs-status srs-status-upcoming">Upcoming</span>';
        } elseif ($today > $end_date) {
            return '<span class="srs-status srs-status-ended">Ended</span>';
        } else {
            return '<span class="srs-status srs-status-active">Active</span>';
        }
    }
    
    public function column_date($item) {
        return get_the_date(get_option('date_format'), $item->ID);
    }
    
    public function column_default($item, $column_name) {
        return '';
    }
}

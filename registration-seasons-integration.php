<?php
/**
 * Integration for registration seasons with family accounts
 */

/**
 * Get active registration seasons
 */
function srs_get_active_seasons() {
    $current_date = current_time('Y-m-d');
    
    $args = array(
        'post_type' => 'srs_season',
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'start_date',
                'value' => $current_date,
                'compare' => '<=',
                'type' => 'DATE',
            ),
            array(
                'key' => 'end_date',
                'value' => $current_date,
                'compare' => '>=',
                'type' => 'DATE',
            ),
        ),
    );
    
    return get_posts($args);
}

/**
 * Get upcoming registration seasons
 */
function srs_get_upcoming_seasons() {
    $current_date = current_time('Y-m-d');
    
    $args = array(
        'post_type' => 'srs_season',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'start_date',
                'value' => $current_date,
                'compare' => '>',
                'type' => 'DATE',
            ),
        ),
        'orderby' => 'meta_value',
        'meta_key' => 'start_date',
        'order' => 'ASC',
    );
    
    return get_posts($args);
}

/**
 * Get available registration forms based on active seasons
 */
function srs_get_available_forms() {
    // Check if date-based registrations are enabled
    $global_settings = get_option('srs_global_settings', array());
    $date_based_registrations = isset($global_settings['date_based_registrations']) ? $global_settings['date_based_registrations'] : 0;
    
    if (!$date_based_registrations) {
        // Use all enabled forms if date-based registrations are disabled
        return srs_get_all_enabled_forms();
    }
    
    // Get active seasons
    $active_seasons = srs_get_active_seasons();
    
    if (empty($active_seasons)) {
        return array(); // No active seasons
    }
    
    // Collect all sport types from active seasons
    $available_sport_types = array();
    
    foreach ($active_seasons as $season) {
        $sport_types = get_post_meta($season->ID, 'sport_types', true);
        
        if (!empty($sport_types) && is_array($sport_types)) {
            foreach ($sport_types as $sport_type) {
                if (!isset($available_sport_types[$sport_type])) {
                    $available_sport_types[$sport_type] = array(
                        'season_id' => $season->ID,
                        'season_name' => $season->post_title,
                        'start_date' => get_post_meta($season->ID, 'start_date', true),
                        'end_date' => get_post_meta($season->ID, 'end_date', true),
                        'description' => get_post_meta($season->ID, 'description', true),
                    );
                }
            }
        }
    }
    
    // Get form settings for available sport types
    $available_forms = array();
    
    foreach ($available_sport_types as $sport_type => $season_data) {
        $form_settings = get_option('srs_' . $sport_type . '_settings', array());
        
        if (!empty($form_settings['enabled'])) {
            $available_forms[$sport_type] = array(
                'title' => $form_settings['title'],
                'price' => $form_settings['price'],
                'season' => $season_data['season_name'],
                'season_id' => $season_data['season_id'],
                'start_date' => $season_data['start_date'],
                'end_date' => $season_data['end_date'],
                'description' => $season_data['description'],
            );
        }
    }
    
    return $available_forms;
}

/**
 * Get all enabled forms regardless of seasons
 */
function srs_get_all_enabled_forms() {
    $sport_types = array('basketball', 'soccer', 'cheerleading', 'volleyball');
    $available_forms = array();
    
    foreach ($sport_types as $sport_type) {
        $form_settings = get_option('srs_' . $sport_type . '_settings', array());
        
        if (!empty($form_settings['enabled'])) {
            $available_forms[$sport_type] = array(
                'title' => $form_settings['title'],
                'price' => $form_settings['price'],
            );
        }
    }
    
    return $available_forms;
}

/**
 * Add season information to family dashboard
 */
function srs_add_seasons_to_dashboard() {
    // Check if date-based registrations are enabled
    $global_settings = get_option('srs_global_settings', array());
    $date_based_registrations = isset($global_settings['date_based_registrations']) ? $global_settings['date_based_registrations'] : 0;
    
    if (!$date_based_registrations) {
        return;
    }
    
    // Get upcoming seasons
    $upcoming_seasons = srs_get_upcoming_seasons();
    
    if (empty($upcoming_seasons)) {
        return;
    }
    
    // Display upcoming seasons
    ?>
    <div class="srs-dashboard-section">
        <div class="srs-section-header">
            <h3><?php _e('Upcoming Registration Periods', 'sports-registration'); ?></h3>
        </div>
        
        <div class="srs-upcoming-seasons">
            <?php foreach ($upcoming_seasons as $season): ?>
                <?php
                $start_date = get_post_meta($season->ID, 'start_date', true);
                $end_date = get_post_meta($season->ID, 'end_date', true);
                $sport_types = get_post_meta($season->ID, 'sport_types', true);
                $description = get_post_meta($season->ID, 'description', true);
                
                if (empty($sport_types) || !is_array($sport_types)) {
                    continue;
                }
                
                $sport_labels = array(
                    'basketball' => __('Basketball', 'sports-registration'),
                    'soccer' => __('Soccer', 'sports-registration'),
                    'cheerleading' => __('Cheerleading', 'sports-registration'),
                    'volleyball' => __('Volleyball', 'sports-registration'),
                );
                
                $sports = array();
                
                foreach ($sport_types as $sport) {
                    if (isset($sport_labels[$sport])) {
                        $sports[] = $sport_labels[$sport];
                    }
                }
                
                // Calculate days until registration opens
                $start_date_obj = new DateTime($start_date);
                $today = new DateTime(current_time('Y-m-d'));
                $days_until = $today->diff($start_date_obj)->days;
                ?>
                <div class="srs-upcoming-season">
                    <div class="srs-season-header">
                        <h4><?php echo esc_html($season->post_title); ?></h4>
                        <span class="srs-season-dates">
                            <?php echo esc_html(date_i18n('F j, Y', strtotime($start_date))); ?> - 
                            <?php echo esc_html(date_i18n('F j, Y', strtotime($end_date))); ?>
                        </span>
                    </div>
                    
                    <?php if (!empty($description)): ?>
                        <div class="srs-season-description">
                            <?php echo wp_kses_post($description); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="srs-season-sports">
                        <strong><?php _e('Sports:', 'sports-registration'); ?></strong> <?php echo esc_html(implode(', ', $sports)); ?>
                    </div>
                    
                    <div class="srs-season-countdown">
                        <?php if ($days_until > 0): ?>
                            <span class="srs-countdown-days"><?php echo sprintf(_n('%d day', '%d days', $days_until, 'sports-registration'), $days_until); ?></span>
                            <?php _e('until registration opens', 'sports-registration'); ?>
                        <?php else: ?>
                            <?php _e('Registration opening today!', 'sports-registration'); ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
add_action('srs_dashboard_after_available_registrations', 'srs_add_seasons_to_dashboard');

/**
 * Register admin notification about upcoming seasons
 */
function srs_register_season_notification() {
    // Check if date-based registrations are enabled
    $global_settings = get_option('srs_global_settings', array());
    $date_based_registrations = isset($global_settings['date_based_registrations']) ? $global_settings['date_based_registrations'] : 0;
    
    if (!$date_based_registrations) {
        return;
    }
    
    // Get upcoming seasons in the next 7 days
    $current_date = current_time('Y-m-d');
    $week_later = date('Y-m-d', strtotime('+7 days', strtotime($current_date)));
    
    $args = array(
        'post_type' => 'srs_season',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'start_date',
                'value' => array($current_date, $week_later),
                'compare' => 'BETWEEN',
                'type' => 'DATE',
            ),
        ),
        'orderby' => 'meta_value',
        'meta_key' => 'start_date',
        'order' => 'ASC',
    );
    
    $upcoming_seasons = get_posts($args);
    
    if (empty($upcoming_seasons)) {
        return;
    }
    
    // Add admin notification
    add_action('admin_notices', function() use ($upcoming_seasons) {
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong><?php _e('Sports Registration Notification:', 'sports-registration'); ?></strong>
                <?php
                if (count($upcoming_seasons) === 1) {
                    $season = $upcoming_seasons[0];
                    $start_date = get_post_meta($season->ID, 'start_date', true);
                    $sport_types = get_post_meta($season->ID, 'sport_types', true);
                    
                    $sport_labels = array(
                        'basketball' => __('Basketball', 'sports-registration'),
                        'soccer' => __('Soccer', 'sports-registration'),
                        'cheerleading' => __('Cheerleading', 'sports-registration'),
                        'volleyball' => __('Volleyball', 'sports-registration'),
                    );
                    
                    $sports = array();
                    
                    foreach ($sport_types as $sport) {
                        if (isset($sport_labels[$sport])) {
                            $sports[] = $sport_labels[$sport];
                        }
                    }
                    
                    echo sprintf(
                        __('The registration period for %s (%s) will open on %s.', 'sports-registration'),
                        '<strong>' . esc_html($season->post_title) . '</strong>',
                        esc_html(implode(', ', $sports)),
                        '<strong>' . esc_html(date_i18n(get_option('date_format'), strtotime($start_date))) . '</strong>'
                    );
                } else {
                    echo sprintf(
                        __('%d registration periods will open in the next 7 days. <a href="%s">View details</a>', 'sports-registration'),
                        count($upcoming_seasons),
                        admin_url('admin.php?page=sports-registration-seasons')
                    );
                }
                ?>
            </p>
        </div>
        <?php
    });
}
add_action('admin_init', 'srs_register_season_notification');

/**
 * Schedule automatic email reminders for upcoming seasons
 */
function srs_schedule_season_reminders() {
    // Check if reminders are already scheduled
    if (wp_next_scheduled('srs_send_season_reminders')) {
        return;
    }
    
    // Schedule daily check
    wp_schedule_event(time(), 'daily', 'srs_send_season_reminders');
}
add_action('wp', 'srs_schedule_season_reminders');

/**
 * Send email reminders for upcoming seasons
 */
function srs_send_season_reminders() {
    // Check if date-based registrations are enabled
    $global_settings = get_option('srs_global_settings', array());
    $date_based_registrations = isset($global_settings['date_based_registrations']) ? $global_settings['date_based_registrations'] : 0;
    
    if (!$date_based_registrations) {
        return;
    }
    
    // Get upcoming seasons in the next 3 days
    $current_date = current_time('Y-m-d');
    $three_days_later = date('Y-m-d', strtotime('+3 days', strtotime($current_date)));
    
    $args = array(
        'post_type' => 'srs_season',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'start_date',
                'value' => array($current_date, $three_days_later),
                'compare' => 'BETWEEN',
                'type' => 'DATE',
            ),
        ),
        'orderby' => 'meta_value',
        'meta_key' => 'start_date',
        'order' => 'ASC',
    );
    
    $upcoming_seasons = get_posts($args);
    
    if (empty($upcoming_seasons)) {
        return;
    }
    
    // Get family accounts
    $family_args = array(
        'post_type' => 'srs_family',
        'posts_per_page' => -1,
    );
    
    $families = get_posts($family_args);
    
    if (empty($families)) {
        return;
    }
    
    // Send email to each family
    foreach ($families as $family) {
        $email = get_post_meta($family->ID, 'email', true);
        $first_name = get_post_meta($family->ID, 'first_name', true);
        $last_name = get_post_meta($family->ID, 'last_name', true);
        
        if (empty($email)) {
            continue;
        }
        
        // Compose email
        $subject = __('Upcoming Sports Registration', 'sports-registration');
        
        $message = sprintf(
            __('Hello %s %s,', 'sports-registration'),
            $first_name,
            $last_name
        ) . "\n\n";
        
        $message .= __('We wanted to remind you about the upcoming registration periods:', 'sports-registration') . "\n\n";
        
        foreach ($upcoming_seasons as $season) {
            $start_date = get_post_meta($season->ID, 'start_date', true);
            $end_date = get_post_meta($season->ID, 'end_date', true);
            $sport_types = get_post_meta($season->ID, 'sport_types', true);
            
            $sport_labels = array(
                'basketball' => __('Basketball', 'sports-registration'),
                'soccer' => __('Soccer', 'sports-registration'),
                'cheerleading' => __('Cheerleading', 'sports-registration'),
                'volleyball' => __('Volleyball', 'sports-registration'),
            );
            
            $sports = array();
            
            foreach ($sport_types as $sport) {
                if (isset($sport_labels[$sport])) {
                    $sports[] = $sport_labels[$sport];
                }
            }
            
            $message .= sprintf(
                __('%s (%s): %s to %s', 'sports-registration'),
                $season->post_title,
                implode(', ', $sports),
                date_i18n(get_option('date_format'), strtotime($start_date)),
                date_i18n(get_option('date_format'), strtotime($end_date))
            ) . "\n";
        }
        
        $message .= "\n";
        
        $dashboard_url = srs_get_dashboard_url();
        
        $message .= sprintf(
            __('Log in to your family dashboard to register: %s', 'sports-registration'),
            $dashboard_url
        ) . "\n\n";
        
        $message .= __('Thank you,', 'sports-registration') . "\n";
        $message .= __('Laurel London Optimist Club', 'sports-registration');
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        wp_mail($email, $subject, $message, $headers);
    }
}
add_action('srs_send_season_reminders', 'srs_send_season_reminders');

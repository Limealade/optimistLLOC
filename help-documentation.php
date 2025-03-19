<?php
/**
 * Help documentation for family accounts system
 */

/**
 * Add help menu to admin
 */
function srs_add_help_menu() {
    add_submenu_page(
        'sports-registration',
        'Help Documentation',
        'Help Docs',
        'manage_options',
        'sports-registration-help',
        'srs_display_help_page'
    );
}
add_action('admin_menu', 'srs_add_help_menu');

/**
 * Display help documentation page
 */
function srs_display_help_page() {
    // Get requested topic
    $topic = isset($_GET['topic']) ? sanitize_text_field($_GET['topic']) : 'overview';
    
    ?>
    <div class="wrap srs-help-wrap">
        <h1><?php _e('Help Documentation', 'sports-registration'); ?></h1>
        
        <div class="srs-help-container">
            <div class="srs-help-sidebar">
                <div class="srs-help-search">
                    <input type="text" id="srs-help-search" placeholder="<?php _e('Search help topics...', 'sports-registration'); ?>">
                </div>
                
                <ul class="srs-help-topics">
                    <li class="<?php echo $topic === 'overview' ? 'active' : ''; ?>">
                        <a href="<?php echo admin_url('admin.php?page=sports-registration-help&topic=overview'); ?>">
                            <?php _e('System Overview', 'sports-registration'); ?>
                        </a>
                    </li>
                    <li class="<?php echo $topic === 'family-accounts' ? 'active' : ''; ?>">
                        <a href="<?php echo admin_url('admin.php?page=sports-registration-help&topic=family-accounts'); ?>">
                            <?php _e('Family Accounts', 'sports-registration'); ?>
                        </a>
                    </li>
                    <li class="<?php echo $topic === 'registration-seasons' ? 'active' : ''; ?>">
                        <a href="<?php echo admin_url('admin.php?page=sports-registration-help&topic=registration-seasons'); ?>">
                            <?php _e('Registration Seasons', 'sports-registration'); ?>
                        </a>
                    </li>
                    <li class="<?php echo $topic === 'payment-integration' ? 'active' : ''; ?>">
                        <a href="<?php echo admin_url('admin.php?page=sports-registration-help&topic=payment-integration'); ?>">
                            <?php _e('Payment Integration', 'sports-registration'); ?>
                        </a>
                    </li>
                    <li class="<?php echo $topic === 'google-sheets' ? 'active' : ''; ?>">
                        <a href="<?php echo admin_url('admin.php?page=sports-registration-help&topic=google-sheets'); ?>">
                            <?php _e('Google Sheets Integration', 'sports-registration'); ?>
                        </a>
                    </li>
                    <li class="<?php echo $topic === 'reports-exports' ? 'active' : ''; ?>">
                        <a href="<?php echo admin_url('admin.php?page=sports-registration-help&topic=reports-exports'); ?>">
                            <?php _e('Reports & Exports', 'sports-registration'); ?>
                        </a>
                    </li>
                    <li class="<?php echo $topic === 'shortcodes' ? 'active' : ''; ?>">
                        <a href="<?php echo admin_url('admin.php?page=sports-registration-help&topic=shortcodes'); ?>">
                            <?php _e('Shortcodes', 'sports-registration'); ?>
                        </a>
                    </li>
                    <li class="<?php echo $topic === 'family-discounts' ? 'active' : ''; ?>">
                        <a href="<?php echo admin_url('admin.php?page=sports-registration-help&topic=family-discounts'); ?>">
                            <?php _e('Family Discounts', 'sports-registration'); ?>
                        </a>
                    </li>
                    <li class="<?php echo $topic === 'troubleshooting' ? 'active' : ''; ?>">
                        <a href="<?php echo admin_url('admin.php?page=sports-registration-help&topic=troubleshooting'); ?>">
                            <?php _e('Troubleshooting', 'sports-registration'); ?>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="srs-help-content">
                <?php srs_display_help_topic($topic); ?>
            </div>
        </div>
    </div>
    
    <script>
        jQuery(document).ready(function($) {
            // Search functionality
            $('#srs-help-search').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('.srs-help-topics li').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
            });
        });
    </script>
    <?php
}

/**
 * Display help topic
 */
function srs_display_help_topic($topic) {
    switch ($topic) {
        case 'overview':
            srs_display_overview_help();
            break;
            
        case 'family-accounts':
            srs_display_family_accounts_help();
            break;
            
        case 'registration-seasons':
            srs_display_registration_seasons_help();
            break;
            
        case 'payment-integration':
            srs_display_payment_integration_help();
            break;
            
        case 'google-sheets':
            srs_display_google_sheets_help();
            break;
            
        case 'reports-exports':
            srs_display_reports_exports_help();
            break;
            
        case 'shortcodes':
            srs_display_shortcodes_help();
            break;
            
        case 'family-discounts':
            srs_display_family_discounts_help();
            break;
            
        case 'troubleshooting':
            srs_display_troubleshooting_help();
            break;
            
        default:
            srs_display_overview_help();
            break;
    }
}

/**
 * Display overview help
 */
function srs_display_overview_help() {
    ?>
    <h2><?php _e('System Overview', 'sports-registration'); ?></h2>
    
    <div class="srs-help-section">
        <p><?php _e('The Sports Registration System provides a comprehensive solution for managing registrations for various sports. The system includes the following key features:', 'sports-registration'); ?></p>
        
        <ul class="srs-help-list">
            <li><strong><?php _e('Registration Forms:', 'sports-registration'); ?></strong> <?php _e('Create customizable registration forms for different sports (basketball, soccer, cheerleading, volleyball).', 'sports-registration'); ?></li>
            <li><strong><?php _e('Family Accounts:', 'sports-registration'); ?></strong> <?php _e('Allow parents to create accounts and manage their children\'s profiles for streamlined registration.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Registration Seasons:', 'sports-registration'); ?></strong> <?php _e('Set up date-based registration periods for different sports.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Payment Processing:', 'sports-registration'); ?></strong> <?php _e('Accept payments through Square and PayPal integration.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Family Discounts:', 'sports-registration'); ?></strong> <?php _e('Automatically apply discounts for multiple children from the same family.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Google Sheets Integration:', 'sports-registration'); ?></strong> <?php _e('Automatically sync registration data to Google Sheets.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Reports & Exports:', 'sports-registration'); ?></strong> <?php _e('Generate various reports and export data in CSV, Excel, or PDF formats.', 'sports-registration'); ?></li>
        </ul>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Getting Started', 'sports-registration'); ?></h3>
        
        <ol class="srs-help-steps">
            <li><?php _e('Configure global settings in <strong>Sports Registration > Global Settings</strong>.', 'sports-registration'); ?></li>
            <li><?php _e('Set up individual sport settings in their respective pages.', 'sports-registration'); ?></li>
            <li><?php _e('If using family accounts, create pages with the family dashboard and login shortcodes.', 'sports-registration'); ?></li>
            <li><?php _e('If using date-based registrations, create registration seasons.', 'sports-registration'); ?></li>
            <li><?php _e('Add registration forms to your pages using the shortcode or Gutenberg block.', 'sports-registration'); ?></li>
        </ol>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Admin Menu Structure', 'sports-registration'); ?></h3>
        
        <ul class="srs-help-list">
            <li><strong><?php _e('Dashboard:', 'sports-registration'); ?></strong> <?php _e('Overview of registrations and system stats.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Global Settings:', 'sports-registration'); ?></strong> <?php _e('Configure system-wide settings.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Sport Settings:', 'sports-registration'); ?></strong> <?php _e('Configure settings for each sport.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Registrations:', 'sports-registration'); ?></strong> <?php _e('View and manage all registrations.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Family Accounts:', 'sports-registration'); ?></strong> <?php _e('Manage family accounts.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Child Profiles:', 'sports-registration'); ?></strong> <?php _e('Manage child profiles.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Seasons:', 'sports-registration'); ?></strong> <?php _e('Manage registration seasons.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Reports & Exports:', 'sports-registration'); ?></strong> <?php _e('Generate reports and exports.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Help Docs:', 'sports-registration'); ?></strong> <?php _e('Access help documentation.', 'sports-registration'); ?></li>
        </ul>
    </div>
    <?php
}

/**
 * Display family accounts help
 */
function srs_display_family_accounts_help() {
    ?>
    <h2><?php _e('Family Accounts', 'sports-registration'); ?></h2>
    
    <div class="srs-help-section">
        <p><?php _e('The Family Accounts feature allows parents to create accounts, save their family information, and manage their children\'s profiles. This streamlines the registration process for families with multiple children.', 'sports-registration'); ?></p>
        
        <h3><?php _e('How It Works', 'sports-registration'); ?></h3>
        
        <ol class="srs-help-steps">
            <li><?php _e('Parents create a family account with their contact information.', 'sports-registration'); ?></li>
            <li><?php _e('Parents add children to their family profile, including details like name, gender, date of birth, etc.', 'sports-registration'); ?></li>
            <li><?php _e('When registration is open, parents can select which children to register for specific sports.', 'sports-registration'); ?></li>
            <li><?php _e('Family discounts are automatically applied when registering multiple children.', 'sports-registration'); ?></li>
            <li><?php _e('Registration history is tracked for each child.', 'sports-registration'); ?></li>
        </ol>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Setting Up Family Accounts', 'sports-registration'); ?></h3>
        
        <ol class="srs-help-steps">
            <li><?php _e('Go to <strong>Sports Registration > Global Settings</strong>.', 'sports-registration'); ?></li>
            <li><?php _e('In the <strong>Family Account Settings</strong> section, check <strong>Enable Family Accounts</strong>.', 'sports-registration'); ?></li>
            <li><?php _e('Create two pages: one for the family dashboard and one for the login/registration form.', 'sports-registration'); ?></li>
            <li><?php _e('Add the shortcode <code>[srs_family_dashboard]</code> to the dashboard page.', 'sports-registration'); ?></li>
            <li><?php _e('Add the shortcode <code>[srs_family_login]</code> to the login page.', 'sports-registration'); ?></li>
            <li><?php _e('Select these pages in the settings to complete the setup.', 'sports-registration'); ?></li>
        </ol>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Managing Family Accounts', 'sports-registration'); ?></h3>
        
        <p><?php _e('Administrators can manage family accounts through the admin interface:', 'sports-registration'); ?></p>
        
        <ul class="srs-help-list">
            <li><?php _e('View all family accounts in <strong>Sports Registration > Family Accounts</strong>.', 'sports-registration'); ?></li>
            <li><?php _e('View all child profiles in <strong>Sports Registration > Child Profiles</strong>.', 'sports-registration'); ?></li>
            <li><?php _e('Each family account shows the associated children and registration history.', 'sports-registration'); ?></li>
            <li><?php _e('Each child profile shows the associated family and registration history.', 'sports-registration'); ?></li>
        </ul>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Family Dashboard Features', 'sports-registration'); ?></h3>
        
        <ul class="srs-help-list">
            <li><strong><?php _e('Family Profile:', 'sports-registration'); ?></strong> <?php _e('Parents can update their contact information.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Child Management:', 'sports-registration'); ?></strong> <?php _e('Parents can add, edit, and remove children from their profile.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Registration Options:', 'sports-registration'); ?></strong> <?php _e('Shows available sports registration options based on active seasons.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Registration History:', 'sports-registration'); ?></strong> <?php _e('Displays registration history for each child.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Upcoming Seasons:', 'sports-registration'); ?></strong> <?php _e('Shows information about upcoming registration periods.', 'sports-registration'); ?></li>
        </ul>
    </div>
    
    <div class="srs-help-note">
        <p><?php _e('<strong>Note:</strong> Family accounts are stored separately from WordPress users. The system uses a custom authentication method.', 'sports-registration'); ?></p>
    </div>
    <?php
}

/**
 * Display registration seasons help
 */
function srs_display_registration_seasons_help() {
    ?>
    <h2><?php _e('Registration Seasons', 'sports-registration'); ?></h2>
    
    <div class="srs-help-section">
        <p><?php _e('Registration Seasons allow you to define specific time periods when registration forms for different sports are available. This feature helps you organize registrations around your sports seasons.', 'sports-registration'); ?></p>
        
        <h3><?php _e('How It Works', 'sports-registration'); ?></h3>
        
        <ol class="srs-help-steps">
            <li><?php _e('Create registration seasons with start and end dates.', 'sports-registration'); ?></li>
            <li><?php _e('Assign sports to each season.', 'sports-registration'); ?></li>
            <li><?php _e('When a season is active (current date is between start and end date), the registration forms for the assigned sports become available.', 'sports-registration'); ?></li>
            <li><?php _e('When the season ends, the registration forms are automatically hidden.', 'sports-registration'); ?></li>
        </ol>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Setting Up Registration Seasons', 'sports-registration'); ?></h3>
        
        <ol class="srs-help-steps">
            <li><?php _e('Go to <strong>Sports Registration > Global Settings</strong>.', 'sports-registration'); ?></li>
            <li><?php _e('In the <strong>Registration Seasons</strong> section, check <strong>Enable Date-Based Registrations</strong>.', 'sports-registration'); ?></li>
            <li><?php _e('Go to <strong>Sports Registration > Seasons</strong>.', 'sports-registration'); ?></li>
            <li><?php _e('Click <strong>Add New Season</strong> to create a registration season.', 'sports-registration'); ?></li>
            <li><?php _e('Enter a name for the season, set the start and end dates, and select the sports to include.', 'sports-registration'); ?></li>
            <li><?php _e('Add a description if desired, and save the season.', 'sports-registration'); ?></li>
        </ol>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Managing Registration Seasons', 'sports-registration'); ?></h3>
        
        <ul class="srs-help-list">
            <li><?php _e('View all seasons in <strong>Sports Registration > Seasons</strong>.', 'sports-registration'); ?></li>
            <li><?php _e('Edit a season to change dates, sports, or description.', 'sports-registration'); ?></li>
            <li><?php _e('Season status is automatically determined based on current date::', 'sports-registration'); ?>
                <ul>
                    <li><strong><?php _e('Upcoming:', 'sports-registration'); ?></strong> <?php _e('Start date is in the future.', 'sports-registration'); ?></li>
                    <li><strong><?php _e('Active:', 'sports-registration'); ?></strong> <?php _e('Current date is between start and end dates.', 'sports-registration'); ?></li>
                    <li><strong><?php _e('Ended:', 'sports-registration'); ?></strong> <?php _e('End date is in the past.', 'sports-registration'); ?></li>
                </ul>
            </li>
        </ul>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Notification System', 'sports-registration'); ?></h3>
        
        <p><?php _e('The system includes automatic notifications for upcoming seasons:', 'sports-registration'); ?></p>
        
        <ul class="srs-help-list">
            <li><?php _e('Admin notifications about seasons starting within the next 7 days.', 'sports-registration'); ?></li>
            <li><?php _e('Email reminders to families about upcoming registration periods (3 days before start).', 'sports-registration'); ?></li>
            <li><?php _e('Display of upcoming seasons on the family dashboard.', 'sports-registration'); ?></li>
        </ul>
    </div>
    
    <div class="srs-help-note">
        <p><?php _e('<strong>Note:</strong> If date-based registrations are disabled, all enabled registration forms will be available at all times.', 'sports-registration'); ?></p>
    </div>
    <?php
}

/**
 * Display payment integration help
 */
function srs_display_payment_integration_help() {
    ?>
    <h2><?php _e('Payment Integration', 'sports-registration'); ?></h2>
    
    <div class="srs-help-section">
        <p><?php _e('The Sports Registration System integrates with Square and PayPal for payment processing. This allows you to collect registration fees online.', 'sports-registration'); ?></p>
        
        <h3><?php _e('Square Integration', 'sports-registration'); ?></h3>
        
        <h4><?php _e('Setup Instructions', 'sports-registration'); ?></h4>
        <ol class="srs-help-steps">
            <li><?php _e('Create a Square Developer account at <a href="https://developer.squareup.com/" target="_blank">https://developer.squareup.com/</a>', 'sports-registration'); ?></li>
            <li><?php _e('Create a new application in the Square Developer Dashboard.', 'sports-registration'); ?></li>
            <li><?php _e('Go to <strong>Sports Registration > Global Settings</strong>.', 'sports-registration'); ?></li>
            <li><?php _e('In the <strong>Payment Gateways</strong> section, check <strong>Enable Square</strong>.', 'sports-registration'); ?></li>
            <li><?php _e('Enter your Square Application ID, Location ID, and Access Token.', 'sports-registration'); ?></li>
            <li><?php _e('Save your settings.', 'sports-registration'); ?></li>
        </ol>
        
        <h4><?php _e('Required Square Credentials', 'sports-registration'); ?></h4>
        <ul class="srs-help-list">
            <li><strong><?php _e('Application ID:', 'sports-registration'); ?></strong> <?php _e('Found in your Square Developer Dashboard under Application settings.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Location ID:', 'sports-registration'); ?></strong> <?php _e('Found in the Square Developer Dashboard under Locations.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Access Token:', 'sports-registration'); ?></strong> <?php _e('Generated in the Square Developer Dashboard under the "OAuth" section.', 'sports-registration'); ?></li>
        </ul>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('PayPal Integration', 'sports-registration'); ?></h3>
        
        <h4><?php _e('Setup Instructions', 'sports-registration'); ?></h4>
        <ol class="srs-help-steps">
            <li><?php _e('Create a PayPal Developer account at <a href="https://developer.paypal.com/" target="_blank">https://developer.paypal.com/</a>', 'sports-registration'); ?></li>
            <li><?php _e('Create a new application in the PayPal Developer Dashboard.', 'sports-registration'); ?></li>
            <li><?php _e('Go to <strong>Sports Registration > Global Settings</strong>.', 'sports-registration'); ?></li>
            <li><?php _e('In the <strong>Payment Gateways</strong> section, check <strong>Enable PayPal</strong>.', 'sports-registration'); ?></li>
            <li><?php _e('Enter your PayPal Client ID and Secret.', 'sports-registration'); ?></li>
            <li><?php _e('Save your settings.', 'sports-registration'); ?></li>
        </ol>
        
        <h4><?php _e('Required PayPal Credentials', 'sports-registration'); ?></h4>
        <ul class="srs-help-list">
            <li><strong><?php _e('Client ID:', 'sports-registration'); ?></strong> <?php _e('Found in your PayPal Developer Dashboard under your application settings.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Secret:', 'sports-registration'); ?></strong> <?php _e('Found in your PayPal Developer Dashboard under your application settings.', 'sports-registration'); ?></li>
        </ul>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Setting Registration Fees', 'sports-registration'); ?></h3>
        
        <ol class="srs-help-steps">
            <li><?php _e('Go to <strong>Sports Registration > Global Settings</strong>.', 'sports-registration'); ?></li>
            <li><?php _e('In the <strong>Pricing & Family Discounts</strong> section, set the base registration fee for each sport.', 'sports-registration'); ?></li>
            <li><?php _e('You can also configure family discounts for multiple children from the same family.', 'sports-registration'); ?></li>
        </ol>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Payment Reports', 'sports-registration'); ?></h3>
        
        <p><?php _e('View payment information in the following ways:', 'sports-registration'); ?></p>
        
        <ul class="srs-help-list">
            <li><?php _e('Dashboard shows summary of payments and revenue.', 'sports-registration'); ?></li>
            <li><?php _e('Registration details include payment information.', 'sports-registration'); ?></li>
            <li><?php _e('Generate financial reports in <strong>Sports Registration > Reports & Exports</strong>.', 'sports-registration'); ?></li>
        </ul>
    </div>
    
    <div class="srs-help-note">
        <p><?php _e('<strong>Note:</strong> Make sure your website uses HTTPS (SSL) for secure payment processing.', 'sports-registration'); ?></p>
    </div>
    <?php
}

/**
 * Display Google Sheets help
 */
function srs_display_google_sheets_help() {
    ?>
    <h2><?php _e('Google Sheets Integration', 'sports-registration'); ?></h2>
    
    <div class="srs-help-section">
        <p><?php _e('The Sports Registration System can automatically sync registration data to Google Sheets. This allows you to view and analyze registration data in real-time.', 'sports-registration'); ?></p>
        
        <h3><?php _e('How It Works', 'sports-registration'); ?></h3>
        
        <ol class="srs-help-steps">
            <li><?php _e('When a registration is submitted, the data is saved to the database.', 'sports-registration'); ?></li>
            <li><?php _e('If Google Sheets integration is enabled, the system also sends the data to Google Sheets.', 'sports-registration'); ?></li>
            <li><?php _e('Each sport gets its own sheet in the spreadsheet.', 'sports-registration'); ?></li>
            <li><?php _e('The system automatically creates and formats the sheets as needed.', 'sports-registration'); ?></li>
        </ol>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Setting Up Google Sheets Integration', 'sports-registration'); ?></h3>
        
        <h4><?php _e('Step 1: Create a Google Service Account', 'sports-registration'); ?></h4>
        <ol class="srs-help-steps">
            <li><?php _e('Go to the <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a>.', 'sports-registration'); ?></li>
            <li><?php _e('Create a new project.', 'sports-registration'); ?></li>
            <li><?php _e('Navigate to "APIs & Services" > "Library".', 'sports-registration'); ?></li>
            <li><?php _e('Search for and enable the "Google Sheets API".', 'sports-registration'); ?></li>
            <li><?php _e('Navigate to "APIs & Services" > "Credentials".', 'sports-registration'); ?></li>
            <li><?php _e('Click "Create Credentials" and select "Service Account".', 'sports-registration'); ?></li>
            <li><?php _e('Fill in the service account details and click "Create".', 'sports-registration'); ?></li>
            <li><?php _e('In the service account permissions, click "Done".', 'sports-registration'); ?></li>
            <li><?php _e('Click on the service account to view its details.', 'sports-registration'); ?></li>
            <li><?php _e('In the "Keys" tab, click "Add Key" > "Create new key".', 'sports-registration'); ?></li>
            <li><?php _e('Select "JSON" and click "Create". This will download a JSON file.', 'sports-registration'); ?></li>
        </ol>
        
        <h4><?php _e('Step 2: Create a Google Sheet', 'sports-registration'); ?></h4>
        <ol class="srs-help-steps">
            <li><?php _e('Create a new Google Sheet.', 'sports-registration'); ?></li>
            <li><?php _e('Click the "Share" button.', 'sports-registration'); ?></li>
            <li><?php _e('Add the service account email address (found in the service account details) with Editor permissions.', 'sports-registration'); ?></li>
            <li><?php _e('Copy the Google Sheet ID from the URL. It\'s the long string of characters between "/d/" and "/edit" in the URL.', 'sports-registration'); ?></li>
        </ol>
        
        <h4><?php _e('Step 3: Configure the Plugin', 'sports-registration'); ?></h4>
        <ol class="srs-help-steps">
            <li><?php _e('Go to <strong>Sports Registration > Global Settings</strong>.', 'sports-registration'); ?></li>
            <li><?php _e('In the <strong>Google Sheets Integration</strong> section, check <strong>Enable Google Sheets Integration</strong>.', 'sports-registration'); ?></li>
            <li><?php _e('Enter the Google Sheet ID.', 'sports-registration'); ?></li>
            <li><?php _e('Copy and paste the contents of the JSON file you downloaded into the Service Account JSON field.', 'sports-registration'); ?></li>
            <li><?php _e('Click "Test Connection" to verify the configuration.', 'sports-registration'); ?></li>
            <li><?php _e('Save your settings.', 'sports-registration'); ?></li>
        </ol>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Google Sheets Structure', 'sports-registration'); ?></h3>
        
        <p><?php _e('The system creates the following sheets in your Google Spreadsheet:', 'sports-registration'); ?></p>
        
        <ul class="srs-help-list">
            <li><strong><?php _e('Basketball:', 'sports-registration'); ?></strong> <?php _e('Contains basketball registrations.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Soccer:', 'sports-registration'); ?></strong> <?php _e('Contains soccer registrations.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Cheerleading:', 'sports-registration'); ?></strong> <?php _e('Contains cheerleading registrations.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Volleyball:', 'sports-registration'); ?></strong> <?php _e('Contains volleyball registrations.', 'sports-registration'); ?></li>
        </ul>
        
        <p><?php _e('Each sheet includes columns for all registration fields, including:', 'sports-registration'); ?></p>
        
        <ul class="srs-help-list">
            <li><?php _e('Personal information (name, gender, date of birth, etc.)', 'sports-registration'); ?></li>
            <li><?php _e('Contact information (address, phone, etc.)', 'sports-registration'); ?></li>
            <li><?php _e('Medical information', 'sports-registration'); ?></li>
            <li><?php _e('Emergency contact information', 'sports-registration'); ?></li>
            <li><?php _e('Payment information', 'sports-registration'); ?></li>
            <li><?php _e('Submission date and time', 'sports-registration'); ?></li>
        </ul>
    </div>
    
    <div class="srs-help-note">
        <p><?php _e('<strong>Note:</strong> The Google Sheets API has rate limits. If you expect a very high volume of registrations, consider implementing a queue system.', 'sports-registration'); ?></p>
    </div>
    <?php
}

/**
 * Display reports and exports help
 */
function srs_display_reports_exports_help() {
    ?>
    <h2><?php _e('Reports & Exports', 'sports-registration'); ?></h2>
    
    <div class="srs-help-section">
        <p><?php _e('The Sports Registration System provides comprehensive reporting and data export options. This allows you to analyze registration data and export it for use in other systems.', 'sports-registration'); ?></p>
        
        <h3><?php _e('Available Reports', 'sports-registration'); ?></h3>
        
        <ul class="srs-help-list">
            <li><strong><?php _e('Registrations:', 'sports-registration'); ?></strong> <?php _e('Detailed list of all registrations with all form fields.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Family Accounts:', 'sports-registration'); ?></strong> <?php _e('List of all family accounts and their information.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Child Profiles:', 'sports-registration'); ?></strong> <?php _e('List of all child profiles and their information.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Team Roster:', 'sports-registration'); ?></strong> <?php _e('Formatted list of registrants for team organization.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Financial Report:', 'sports-registration'); ?></strong> <?php _e('Summary of payments, fees, and discounts.', 'sports-registration'); ?></li>
        </ul>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Export Formats', 'sports-registration'); ?></h3>
        
        <ul class="srs-help-list">
            <li><strong><?php _e('CSV:', 'sports-registration'); ?></strong> <?php _e('Simple comma-separated values format compatible with most spreadsheet applications.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Excel:', 'sports-registration'); ?></strong> <?php _e('Native Excel format with proper formatting (requires PHPExcel library).', 'sports-registration'); ?></li>
            <li><strong><?php _e('PDF:', 'sports-registration'); ?></strong> <?php _e('Formatted PDF document for printing or sharing (requires TCPDF library).', 'sports-registration'); ?></li>
        </ul>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Generating Reports', 'sports-registration'); ?></h3>
        
        <ol class="srs-help-steps">
            <li><?php _e('Go to <strong>Sports Registration > Reports & Exports</strong>.', 'sports-registration'); ?></li>
            <li><?php _e('Select the export type (Registrations, Family Accounts, etc.).', 'sports-registration'); ?></li>
            <li><?php _e('Apply filters if desired (sport type, season, etc.).', 'sports-registration'); ?></li>
            <li><?php _e('Select the export format (CSV, Excel, PDF).', 'sports-registration'); ?></li>
            <li><?php _e('Click "Generate Export" to download the report.', 'sports-registration'); ?></li>
        </ol>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Dashboard Summary', 'sports-registration'); ?></h3>
        
        <p><?php _e('The Reports & Exports page also includes a summary dashboard showing:', 'sports-registration'); ?></p>
        
        <ul class="srs-help-list">
            <li><?php _e('Total registrations for the current year', 'sports-registration'); ?></li>
            <li><?php _e('Number of paid registrations', 'sports-registration'); ?></li>
            <li><?php _e('Total revenue collected', 'sports-registration'); ?></li>
            <li><?php _e('Number of family accounts and child profiles', 'sports-registration'); ?></li>
            <li><?php _e('Breakdown of registrations by sport', 'sports-registration'); ?></li>
        </ul>
    </div>
    
    <div class="srs-help-note">
        <p><?php _e('<strong>Note:</strong> For Excel and PDF exports, the system requires the PHPExcel and TCPDF libraries, respectively. If these libraries are not available, the system will fall back to CSV format.', 'sports-registration'); ?></p>
    </div>
    <?php
}

/**
 * Display shortcodes help
 */
function srs_display_shortcodes_help() {
    ?>
    <h2><?php _e('Shortcodes', 'sports-registration'); ?></h2>
    
    <div class="srs-help-section">
        <p><?php _e('The Sports Registration System provides several shortcodes to display forms and family account interfaces on your website.', 'sports-registration'); ?></p>
        
        <h3><?php _e('Registration Form Shortcode', 'sports-registration'); ?></h3>
        
        <div class="srs-shortcode-example">
            <code>[srs_registration_form type="basketball"]</code>
        </div>
        
        <p><?php _e('Displays a registration form for the specified sport. The <code>type</code> parameter can be one of the following:', 'sports-registration'); ?></p>
        
        <ul class="srs-help-list">
            <li><code>basketball</code></li>
            <li><code>soccer</code></li>
            <li><code>cheerleading</code></li>
            <li><code>volleyball</code></li>
        </ul>
        
        <p><?php _e('Example:', 'sports-registration'); ?></p>
        
        <div class="srs-shortcode-example">
            <code>[srs_registration_form type="basketball"]</code>
        </div>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Family Dashboard Shortcode', 'sports-registration'); ?></h3>
        
        <div class="srs-shortcode-example">
            <code>[srs_family_dashboard]</code>
        </div>
        
        <p><?php _e('Displays the family dashboard interface. This is where parents can manage their family profile, add/edit children, and register for sports.', 'sports-registration'); ?></p>
        
        <p><?php _e('This shortcode does not accept any parameters.', 'sports-registration'); ?></p>
        
        <p><?php _e('Example:', 'sports-registration'); ?></p>
        
        <div class="srs-shortcode-example">
            <code>[srs_family_dashboard]</code>
        </div>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Family Login Shortcode', 'sports-registration'); ?></h3>
        
        <div class="srs-shortcode-example">
            <code>[srs_family_login]</code>
        </div>
        
        <p><?php _e('Displays the family login and registration forms. This is where parents can create accounts or log in to existing accounts.', 'sports-registration'); ?></p>
        
        <p><?php _e('This shortcode does not accept any parameters.', 'sports-registration'); ?></p>
        
        <p><?php _e('Example:', 'sports-registration'); ?></p>
        
        <div class="srs-shortcode-example">
            <code>[srs_family_login]</code>
        </div>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Registration List Shortcode', 'sports-registration'); ?></h3>
        
        <div class="srs-shortcode-example">
            <code>[srs_registration_list type="basketball" limit="10"]</code>
        </div>
        
        <p><?php _e('Displays a list of current registrations. This is useful for displaying the number of registrants publicly.', 'sports-registration'); ?></p>
        
        <p><?php _e('Parameters:', 'sports-registration'); ?></p>
        
        <ul class="srs-help-list">
            <li><strong>type:</strong> <?php _e('The sport type (basketball, soccer, cheerleading, volleyball). If omitted, shows all types.', 'sports-registration'); ?></li>
            <li><strong>limit:</strong> <?php _e('The maximum number of registrations to show. Default is 10.', 'sports-registration'); ?></li>
            <li><strong>show_count:</strong> <?php _e('Whether to show the total count. Values: true/false. Default is true.', 'sports-registration'); ?></li>
        </ul>
        
        <p><?php _e('Example:', 'sports-registration'); ?></p>
        
        <div class="srs-shortcode-example">
            <code>[srs_registration_list type="basketball" limit="10" show_count="true"]</code>
        </div>
    </div>
    
    <div class="srs-help-note">
        <p><?php _e('<strong>Note:</strong> You can also use the Gutenberg block editor to add registration forms to your pages.', 'sports-registration'); ?></p>
    </div>
    <?php
}

/**
 * Display family discounts help
 */
function srs_display_family_discounts_help() {
    ?>
    <h2><?php _e('Family Discounts', 'sports-registration'); ?></h2>
    
    <div class="srs-help-section">
        <p><?php _e('The Family Discounts feature allows you to offer discounted registration fees for families registering multiple children. This encourages participation from all children in a family.', 'sports-registration'); ?></p>
        
        <h3><?php _e('How It Works', 'sports-registration'); ?></h3>
        
        <ol class="srs-help-steps">
            <li><?php _e('You set a base registration fee for each sport.', 'sports-registration'); ?></li>
            <li><?php _e('You define discount percentages for the second, third, and additional children.', 'sports-registration'); ?></li>
            <li><?php _e('When a family registers multiple children, the system automatically applies the appropriate discounts.', 'sports-registration'); ?></li>
            <li><?php _e('The system determines family relationships based on last name, address, and contact information.', 'sports-registration'); ?></li>
        </ol>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Setting Up Family Discounts', 'sports-registration'); ?></h3>
        
        <ol class="srs-help-steps">
            <li><?php _e('Go to <strong>Sports Registration > Global Settings</strong>.', 'sports-registration'); ?></li>
            <li><?php _e('In the <strong>Pricing & Family Discounts</strong> section:', 'sports-registration'); ?>
                <ul>
                    <li><?php _e('Set the base registration fee for each sport.', 'sports-registration'); ?></li>
                    <li><?php _e('Check <strong>Enable Family Discounts</strong>.', 'sports-registration'); ?></li>
                    <li><?php _e('Set the discount percentage for the second child.', 'sports-registration'); ?></li>
                    <li><?php _e('Set the discount percentage for the third child.', 'sports-registration'); ?></li>
                    <li><?php _e('Set the discount percentage for additional children (fourth and beyond).', 'sports-registration'); ?></li>
                </ul>
            </li>
            <li><?php _e('Save your settings.', 'sports-registration'); ?></li>
        </ol>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Discount Calculation Example', 'sports-registration'); ?></h3>
        
        <p><?php _e('Let\'s say you have the following configuration:', 'sports-registration'); ?></p>
        
        <ul class="srs-help-list">
            <li><?php _e('Basketball registration fee: $50', 'sports-registration'); ?></li>
            <li><?php _e('Second child discount: 10%', 'sports-registration'); ?></li>
            <li><?php _e('Third child discount: 15%', 'sports-registration'); ?></li>
            <li><?php _e('Additional children discount: 20%', 'sports-registration'); ?></li>
        </ul>
        
        <p><?php _e('If a family registers three children for basketball, they would pay:', 'sports-registration'); ?></p>
        
        <ul class="srs-help-list">
            <li><?php _e('First child: $50 (full price)', 'sports-registration'); ?></li>
            <li><?php _e('Second child: $45 ($50 - 10% discount)', 'sports-registration'); ?></li>
            <li><?php _e('Third child: $42.50 ($50 - 15% discount)', 'sports-registration'); ?></li>
            <li><?php _e('Total: $137.50 (instead of $150)', 'sports-registration'); ?></li>
        </ul>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Family Discount Display', 'sports-registration'); ?></h3>
        
        <p><?php _e('When a parent is registering children through the family dashboard:', 'sports-registration'); ?></p>
        
        <ul class="srs-help-list">
            <li><?php _e('The system shows a fee breakdown for each child.', 'sports-registration'); ?></li>
            <li><?php _e('Any applicable discounts are clearly displayed.', 'sports-registration'); ?></li>
            <li><?php _e('The total amount due is calculated and shown.', 'sports-registration'); ?></li>
        </ul>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Family Identification', 'sports-registration'); ?></h3>
        
        <p><?php _e('The system identifies families in two ways:', 'sports-registration'); ?></p>
        
        <ol class="srs-help-steps">
            <li><strong><?php _e('Family Accounts:', 'sports-registration'); ?></strong> <?php _e('When using the family account system, children are directly linked to their family account.', 'sports-registration'); ?></li>
            <li><strong><?php _e('Registration Information:', 'sports-registration'); ?></strong> <?php _e('When using traditional registration forms, the system identifies families based on last name, address, and contact information.', 'sports-registration'); ?></li>
        </ol>
    </div>
    
    <div class="srs-help-note">
        <p><?php _e('<strong>Note:</strong> Family discounts are applied based on the current calendar year. Each year, the discount counting starts fresh.', 'sports-registration'); ?></p>
    </div>
    <?php
}

/**
 * Display troubleshooting help
 */
function srs_display_troubleshooting_help() {
    ?>
    <h2><?php _e('Troubleshooting', 'sports-registration'); ?></h2>
    
    <div class="srs-help-section">
        <h3><?php _e('Common Issues and Solutions', 'sports-registration'); ?></h3>
        
        <div class="srs-troubleshooting-item">
            <h4><?php _e('Registration Form Not Displaying', 'sports-registration'); ?></h4>
            
            <p><strong><?php _e('Possible Causes:', 'sports-registration'); ?></strong></p>
            <ul>
                <li><?php _e('Shortcode syntax is incorrect.', 'sports-registration'); ?></li>
                <li><?php _e('The sport type is disabled in settings.', 'sports-registration'); ?></li>
                <li><?php _e('Date-based registrations are enabled, but no active season includes this sport.', 'sports-registration'); ?></li>
            </ul>
            
            <p><strong><?php _e('Solutions:', 'sports-registration'); ?></strong></p>
            <ul>
                <li><?php _e('Verify the shortcode syntax: <code>[srs_registration_form type="basketball"]</code>', 'sports-registration'); ?></li>
                <li><?php _e('Check if the sport is enabled in its settings page.', 'sports-registration'); ?></li>
                <li><?php _e('If using date-based registrations, ensure there\'s an active season that includes this sport.', 'sports-registration'); ?></li>
                <li><?php _e('Temporarily disable date-based registrations to test if that\'s the issue.', 'sports-registration'); ?></li>
            </ul>
        </div>
        
        <div class="srs-troubleshooting-item">
            <h4><?php _e('Payment Processing Errors', 'sports-registration'); ?></h4>
            
            <p><strong><?php _e('Possible Causes:', 'sports-registration'); ?></strong></p>
            <ul>
                <li><?php _e('Invalid API credentials for Square or PayPal.', 'sports-registration'); ?></li>
                <li><?php _e('Website is not using HTTPS (SSL).', 'sports-registration'); ?></li>
                <li><?php _e('JavaScript errors on the page.', 'sports-registration'); ?></li>
            </ul>
            
            <p><strong><?php _e('Solutions:', 'sports-registration'); ?></strong></p>
            <ul>
                <li><?php _e('Verify API credentials in the global settings.', 'sports-registration'); ?></li>
                <li><?php _e('Ensure your website uses HTTPS. Payment APIs require secure connections.', 'sports-registration'); ?></li>
                <li><?php _e('Check browser console for JavaScript errors.', 'sports-registration'); ?></li>
                <li><?php _e('Test with a small amount to verify the payment gateway is working.', 'sports-registration'); ?></li>
            </ul>
        </div>
        
        <div class="srs-troubleshooting-item">
            <h4><?php _e('Google Sheets Integration Not Working', 'sports-registration'); ?></h4>
            
            <p><strong><?php _e('Possible Causes:', 'sports-registration'); ?></strong></p>
            <ul>
                <li><?php _e('Invalid service account JSON.', 'sports-registration'); ?></li>
                <li><?php _e('Incorrect Google Sheet ID.', 'sports-registration'); ?></li>
                <li><?php _e('Service account doesn\'t have access to the spreadsheet.', 'sports-registration'); ?></li>
            </ul>
            
            <p><strong><?php _e('Solutions:', 'sports-registration'); ?></strong></p>
            <ul>
                <li><?php _e('Use the "Test Connection" button to check the connection.', 'sports-registration'); ?></li>
                <li><?php _e('Verify the Google Sheet ID (it\'s the long string in the spreadsheet URL).', 'sports-registration'); ?></li>
                <li><?php _e('Make sure you\'ve shared the spreadsheet with the service account email address.', 'sports-registration'); ?></li>
                <li><?php _e('Try creating a new service account and JSON key.', 'sports-registration'); ?></li>
            </ul>
        </div>
        
        <div class="srs-troubleshooting-item">
            <h4><?php _e('Family Account Login Issues', 'sports-registration'); ?></h4>
            
            <p><strong><?php _e('Possible Causes:', 'sports-registration'); ?></strong></p>
            <ul>
                <li><?php _e('Email or password is incorrect.', 'sports-registration'); ?></li>
                <li><?php _e('Session cookies are blocked or disabled.', 'sports-registration'); ?></li>
                <li><?php _e('Account was created with a different email.', 'sports-registration'); ?></li>
            </ul>
            
            <p><strong><?php _e('Solutions:', 'sports-registration'); ?></strong></p>
            <ul>
                <li><?php _e('Verify the email address is correct.', 'sports-registration'); ?></li>
                <li><?php _e('Ensure cookies are enabled in the browser.', 'sports-registration'); ?></li>
                <li><?php _e('Check if the account exists in the admin dashboard.', 'sports-registration'); ?></li>
                <li><?php _e('If needed, create a new password for the user in the admin area.', 'sports-registration'); ?></li>
            </ul>
        </div>
        
        <div class="srs-troubleshooting-item">
            <h4><?php _e('Family Discounts Not Applying', 'sports-registration'); ?></h4>
            
            <p><strong><?php _e('Possible Causes:', 'sports-registration'); ?></strong></p>
            <ul>
                <li><?php _e('Family discounts are not enabled in settings.', 'sports-registration'); ?></li>
                <li><?php _e('The system doesn\'t recognize children as part of the same family.', 'sports-registration'); ?></li>
                <li><?php _e('Registrations were made in different calendar years.', 'sports-registration'); ?></li>
            </ul>
            
            <p><strong><?php _e('Solutions:', 'sports-registration'); ?></strong></p>
            <ul>
                <li><?php _e('Verify family discounts are enabled in global settings.', 'sports-registration'); ?></li>
                <li><?php _e('Ensure last name, address, and zip code match exactly for all family members.', 'sports-registration'); ?></li>
                <li><?php _e('Remember that discounts only apply within the same calendar year.', 'sports-registration'); ?></li>
                <li><?php _e('If using family accounts, make sure all children are added to the family profile.', 'sports-registration'); ?></li>
            </ul>
        </div>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Plugin Conflicts', 'sports-registration'); ?></h3>
        
        <p><?php _e('If you experience issues that might be related to plugin conflicts:', 'sports-registration'); ?></p>
        
        <ol class="srs-help-steps">
            <li><?php _e('Temporarily deactivate other plugins to identify conflicts.', 'sports-registration'); ?></li>
            <li><?php _e('Check for JavaScript errors in the browser console.', 'sports-registration'); ?></li>
            <li><?php _e('Make sure your theme is compatible with modern WordPress features.', 'sports-registration'); ?></li>
            <li><?php _e('If using caching plugins, try clearing the cache after making changes.', 'sports-registration'); ?></li>
        </ol>
    </div>
    
    <div class="srs-help-section">
        <h3><?php _e('Error Logging', 'sports-registration'); ?></h3>
        
        <p><?php _e('The plugin logs errors to help with troubleshooting:', 'sports-registration'); ?></p>
        
        <ul class="srs-help-list">
            <li><?php _e('Check the WordPress debug.log file if WP_DEBUG is enabled.', 'sports-registration'); ?></li>
            <li><?php _e('Google Sheets integration errors are logged with the prefix "Sports Registration System:".', 'sports-registration'); ?></li>
            <li><?php _e('Payment processing errors are also logged for troubleshooting.', 'sports-registration'); ?></li>
        </ul>
    </div>
    
    <div class="srs-help-note">
        <p><?php _e('<strong>Still having issues?</strong> Please contact the plugin developer for support.', 'sports-registration'); ?></p>
    </div>
    <?php
}

/**
 * Add CSS for help documentation
 */
function srs_add_help_css() {
    $screen = get_current_screen();
    
    if ($screen && $screen->id === 'sports-registration_page_sports-registration-help') {
        ?>
        <style>
            .srs-help-wrap {
                margin: 20px 0;
            }
            
            .srs-help-container {
                display: flex;
                gap: 30px;
                margin-top: 20px;
            }
            
            .srs-help-sidebar {
                flex: 0 0 250px;
                background: #fff;
                border: 1px solid #e5e5e5;
                border-radius: 3px;
                box-shadow: 0 1px 1px rgba(0,0,0,0.04);
            }
            
            .srs-help-search {
                padding: 15px;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .srs-help-search input {
                width: 100%;
                padding: 8px;
            }
            
            .srs-help-topics {
                margin: 0;
                padding: 0;
                list-style: none;
            }
            
            .srs-help-topics li {
                margin: 0;
                padding: 0;
            }
            
            .srs-help-topics li a {
                display: block;
                padding: 10px 15px;
                text-decoration: none;
                border-left: 3px solid transparent;
                color: #333;
            }
            
            .srs-help-topics li a:hover {
                background-color: #f8f8f8;
                border-left-color: #ddd;
            }
            
            .srs-help-topics li.active a {
                background-color: #f0f7fb;
                border-left-color: #0073aa;
                color: #0073aa;
                font-weight: 600;
            }
            
            .srs-help-content {
                flex: 1;
                background: #fff;
                padding: 20px;
                border: 1px solid #e5e5e5;
                border-radius: 3px;
                box-shadow: 0 1px 1px rgba(0,0,0,0.04);
            }
            
            .srs-help-content h2 {
                margin-top: 0;
                padding-bottom: 12px;
                border-bottom: 1px solid #eee;
            }
            
            .srs-help-section {
                margin-bottom: 30px;
            }
            
            .srs-help-section h3 {
                margin: 20px 0 10px;
                font-size: 18px;
                color: #23282d;
            }
            
            .srs-help-section h4 {
                margin: 15px 0 10px;
                font-size: 16px;
                color: #23282d;
            }
            
            .srs-help-list,
            .srs-help-steps {
                margin: 15px 0;
                padding-left: 20px;
            }
            
            .srs-help-list li,
            .srs-help-steps li {
                margin-bottom: 8px;
            }
            
            .srs-shortcode-example {
                background-color: #f9f9f9;
                border: 1px solid #e5e5e5;
                border-radius: 3px;
                padding: 10px 15px;
                margin: 10px 0;
                font-family: monospace;
            }
            
            .srs-help-note {
                background-color: #f8f9fa;
                border-left: 4px solid #0073aa;
                padding: 12px 15px;
                margin-top: 20px;
                border-radius: 0 3px 3px 0;
            }
            
            .srs-help-note p {
                margin: 0;
            }
            
            .srs-troubleshooting-item {
                margin-bottom: 25px;
                padding-bottom: 20px;
                border-bottom: 1px dashed #ddd;
            }
            
            .srs-troubleshooting-item:last-child {
                border-bottom: none;
                padding-bottom: 0;
            }
            
            @media (max-width: 782px) {
                .srs-help-container {
                    flex-direction: column;
                }
                
                .srs-help-sidebar {
                    flex: 1;
                    margin-bottom: 20px;
                }
            }
        </style>
        <?php
    }
}
add_action('admin_head', 'srs_add_help_css');

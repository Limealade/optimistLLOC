# Sports Registration System

A comprehensive WordPress plugin for managing sports registrations with support for multiple sports, family accounts, online payments, and Google Sheets integration.

## Features

### Registration Forms
- **Sport-Specific Forms:** Separate customizable forms for basketball, soccer, cheerleading, and volleyball
- **Customizable Fields:** Make any field required or optional based on your needs
- **Mobile-Responsive Design:** Forms work perfectly on all devices
- **Gutenberg Block:** Easy drag-and-drop integration with the WordPress block editor
- **Shortcode Support:** Simple shortcode integration for classic editor users

### Family Account System
- **Parent Accounts:** Parents can create accounts to manage all their children's information
- **Child Profiles:** Store child information once and reuse it for multiple registrations
- **Registration History:** View complete registration history for each child
- **Streamlined Registration:** One-click registration for returning families
- **Secure Authentication:** Custom secure login system for family accounts

### Registration Seasons
- **Date-Based Registrations:** Set specific time periods when each sport is open for registration
- **Automated Availability:** Forms automatically appear and disappear based on your schedule
- **Email Reminders:** Automated reminders for upcoming and active registration periods
- **Multiple Sports Per Season:** Assign multiple sports to each registration period

### Payment Processing
- **Square Integration:** Accept credit card payments through Square
- **PayPal Integration:** Allow payments through PayPal
- **Family Discounts:** Automatic discounts for multiple children from the same family
- **Custom Pricing:** Set different prices for each sport
- **Payment Tracking:** Track payment status and amounts in the admin dashboard

### Google Sheets Integration
- **Automatic Sync:** Registrations automatically added to Google Sheets
- **Custom Sheets:** Separate sheets for each sport
- **Complete Data:** All registration fields included in the spreadsheet
- **Real-Time Updates:** Data is synced immediately upon registration

### Email Notifications
- **Customizable Templates:** Easily customize all email templates
- **Automatic Notifications:** Registration confirmations, payment receipts, and reminders
- **Admin Alerts:** Get notified of new registrations
- **Family Account Emails:** Account creation and child profile notifications

### Reports & Exports
- **Registration Reports:** Generate detailed registration reports
- **Team Rosters:** Create formatted team rosters for coaches
- **Financial Reports:** Track payments, discounts, and revenue
- **Multiple Export Formats:** Export as CSV, Excel, or PDF
- **Filterable Data:** Filter reports by sport, season, payment status, and more

### Admin Dashboard
- **Registration Overview:** See registration statistics at a glance
- **Family Management:** Manage family accounts and child profiles
- **Season Configuration:** Create and manage registration seasons
- **Form Customization:** Configure form fields and requirements for each sport
- **Help Documentation:** Built-in help system for administrators

### Additional Features
- **Dark Mode Support:** Optimized display for users with dark mode preferences
- **GDPR Compliance:** Privacy-friendly data handling
- **Responsive Support:** All interfaces work on mobile, tablet, and desktop
- **Data Security:** Secure data storage and handling
- **Print-Friendly Forms:** Forms can be printed for offline use

## Installation

1. Upload the `sports-registration-system` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin settings under 'Sports Registration' in the admin menu
4. Add registration forms to your pages using the shortcode or Gutenberg block

## Setup Guide

### Basic Configuration
1. Go to **Sports Registration > Global Settings** to configure global options
2. Set up individual sport settings in their respective menu items
3. Create pages for the family dashboard and login form if using family accounts
4. Add registration forms to your pages using the Gutenberg block or shortcode

### Payment Integration
1. Create accounts with Square and/or PayPal developer platforms
2. Enter your API credentials in the global settings
3. Configure pricing for each sport
4. Set up family discounts if desired

### Google Sheets Integration
1. Create a Google Cloud project and enable the Sheets API
2. Create a service account and download the JSON credentials
3. Create a Google Sheet and share it with the service account
4. Enter the Sheet ID and JSON credentials in the global settings

### Registration Seasons
1. Enable date-based registrations in the global settings
2. Create registration seasons with start and end dates
3. Assign sports to each season
4. The correct registration forms will automatically appear during active seasons

## Shortcodes

- `[srs_registration_form type="basketball"]` - Display a registration form (change type as needed)
- `[srs_family_dashboard]` - Display the family dashboard for logged-in parents
- `[srs_family_login]` - Display the family login and registration forms
- `[srs_registration_list type="basketball" limit="10"]` - Display a list of current registrations

## Screenshots

1. Registration Form
2. Family Dashboard
3. Admin Overview
4. Reports & Exports
5. Season Configuration

## Frequently Asked Questions

### Can I use this plugin without the family account system?
Yes, the family account system is optional. You can use traditional registration forms without requiring parents to create accounts.

### How are family discounts calculated?
Family discounts are percentage-based and applied to the second, third, and additional children from the same family. You can configure the discount percentages in the global settings.

### Can I customize the registration forms?
Yes, you can make any field required or optional for each sport. You can also customize the form titles and registration fees.

### How do I set up Square or PayPal payments?
You'll need to create developer accounts with Square and/or PayPal and obtain API credentials. Detailed setup instructions are available in the help documentation.

### How do I get registration data into my Google Sheet?
The plugin automatically syncs registration data to your Google Sheet when properly configured. You'll need to create a Google service account and share your Sheet with it.

### Can I export registration data?
Yes, you can export registration data in CSV, Excel, or PDF format. You can also filter the data by sport, season, payment status, and more.

### Does this work with my theme?
The plugin is designed to work with any WordPress theme. The forms and interfaces are fully responsive and adapt to your theme's styling.

## Support

For support or feature requests, please contact us through our support channels.

## Privacy

This plugin collects personal information for registration purposes. Please ensure you have appropriate privacy policies in place and are compliant with relevant data protection regulations.

## Credits

- Developed by: [Your Name]
- Version: 1.0.0
- License: GPLv2 or later

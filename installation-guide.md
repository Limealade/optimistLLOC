# Sports Registration System - Installation Guide

This WordPress plugin creates registration forms for basketball, soccer, cheerleading, and volleyball with payment processing through Square and PayPal, and Google Sheets integration.

## Installation

### Manual Installation

1. Download the plugin ZIP file
2. Log in to your WordPress admin dashboard
3. Go to **Plugins > Add New**
4. Click the **Upload Plugin** button at the top of the page
5. Choose the downloaded ZIP file and click **Install Now**
6. After installation, click **Activate Plugin**

### Via FTP

1. Download the plugin ZIP file and extract it on your computer
2. Connect to your website using an FTP client
3. Upload the extracted folder to the `/wp-content/plugins/` directory
4. Log in to your WordPress admin dashboard
5. Go to **Plugins** and activate **Sports Registration System**

## Initial Configuration

After installation, follow these steps to configure the plugin:

### 1. Global Settings

1. Go to **Sports Registration > Global Settings**
2. Configure general settings and payment gateways:
   - **Disclosure Text**: Set the text that appears in the disclosure checkbox
   - **Payment Methods**: Enable/disable Square and PayPal
   - **Google Sheets Integration**: Connect with Google Sheets

### 2. Sport-Specific Settings

Configure each sport by visiting:
- **Sports Registration > Basketball**
- **Sports Registration > Soccer**
- **Sports Registration > Cheerleading**
- **Sports Registration > Volleyball**

For each sport, you can:
- Enable/disable the registration form
- Set the registration fee
- Choose which fields are required
- Customize the form title

## Payment Gateway Setup

### Square Integration

1. Create a Square Developer account at https://developer.squareup.com/
2. Create a new application
3. Get your **Application ID** and **Location ID** from the Square Dashboard
4. Generate an **Access Token** in your Square Developer account
5. Enter these credentials in **Sports Registration > Global Settings**

### PayPal Integration

1. Create a PayPal Developer account at https://developer.paypal.com/
2. Create a new application
3. Get your **Client ID** and **Secret**
4. Enter these credentials in **Sports Registration > Global Settings**

## Google Sheets Integration

1. Go to the Google Cloud Console (https://console.cloud.google.com/)
2. Create a new project
3. Enable the Google Sheets API
4. Create a service account
5. Download the JSON credentials file
6. Create a Google Sheet and share it with the service account email
7. Enter the Google Sheet ID and the contents of the JSON file in **Sports Registration > Global Settings**

## Adding Forms to Your Website

### Using the Gutenberg Block

1. Edit a page or post
2. Click the "+" button to add a new block
3. Search for "Sports Registration Form"
4. Select the form type from the block settings

### Using the Shortcode

Use the following shortcode to add a form to any page or post:

```
[srs_registration_form type="basketball"]
```

Replace "basketball" with "soccer", "cheerleading", or "volleyball" as needed.

## Managing Registrations

1. Go to **Sports Registration > Registrations**
2. View and manage all registrations
3. Filter by sport or payment status
4. Export data as needed

## Plugin Dependencies

For full functionality, this plugin requires:

1. **Square PHP SDK** (for Square payments)
2. **Google API Client** (for Google Sheets integration)

These dependencies are automatically included when using the plugin ZIP file. If installing manually, you'll need to install these dependencies using Composer.

## Troubleshooting

### Payment Processing Issues

1. Ensure your API credentials are correct
2. Check that your payment account is properly configured
3. Verify that your site is using HTTPS (required for payment processing)

### Google Sheets Integration Issues

1. Verify that the service account has edit access to the Google Sheet
2. Check that the Google Sheet ID is correct
3. Ensure the service account JSON credentials are properly formatted

## Support

For support or feature requests, please contact the plugin developer at your-email@example.com.

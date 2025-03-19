<?php
/**
 * Plugin Name: Sports Registration System
 * Plugin URI: https://yourwebsite.com/sports-registration
 * Description: Create registration forms for basketball, soccer, cheerleading, and volleyball with Square/PayPal integration and Google Sheets sync.
 * Version: 1.1.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: sports-registration
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SRS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SRS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SRS_PLUGIN_VERSION', '1.1.0');
define('SRS_MINIMUM_WP_VERSION', '5.6');
define('SRS_REQUIRED_PHP_VERSION', '7.4');

/**
 * The main plugin class.
 */
class SRS_Main {
    protected $loader;
    protected $plugin_name;
    protected $version;
    protected $missing_files = array();

    /**
     * Initialize the plugin
     */
    public function __construct() {
        $this->plugin_name = 'sports-registration';
        $this->version = SRS_PLUGIN_VERSION;
        
        // Check system requirements
        if (!$this->check_requirements()) {
            return;
        }
        
        // Load required files
        if (!$this->load_dependencies()) {
            $this->display_missing_files_notice();
            return;
        }
        
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->register_blocks();
    }

    /**
     * Check if system meets requirements
     */
    private function check_requirements() {
        $meets_requirements = true;
        
        // Check PHP version
        if (version_compare(PHP_VERSION, SRS_REQUIRED_PHP_VERSION, '<')) {
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-error">
                    <p>
                        <strong>Sports Registration System:</strong>
                        Requires PHP version <?php echo SRS_REQUIRED_PHP_VERSION; ?> or higher.
                        You are running PHP version <?php echo PHP_VERSION; ?>.
                    </p>
                </div>
                <?php
            });
            $meets_requirements = false;
        }
        
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), SRS_MINIMUM_WP_VERSION, '<')) {
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-error">
                    <p>
                        <strong>Sports Registration System:</strong>
                        Requires WordPress version <?php echo SRS_MINIMUM_WP_VERSION; ?> or higher.
                        You are running WordPress version <?php echo get_bloginfo('version'); ?>.
                    </p>
                </div>
                <?php
            });
            $meets_requirements = false;
        }
        
        return $meets_requirements;
    }

    /**
     * Load all required files
     */
    private function load_dependencies() {
        // Define required files
        $required_files = array(
            'includes/class-srs-database-migration.php',
            'includes/class-srs-loader.php',
            'includes/class-srs-i18n.php',
            'admin/class-srs-admin.php',
            'public/class-srs-public.php',
            'includes/class-srs-forms.php',
            'includes/class-srs-payments.php',
            'includes/class-srs-google-sheet.php',
            'includes/class-srs-email-notifications.php',
        );
        
        // Check and include required files
        $all_files_present = true;
        foreach ($required_files as $file) {
            $file_path = SRS_PLUGIN_DIR . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            } else {
                $this->missing_files[] = $file;
                $all_files_present = false;
            }
        }
        
        // Create loader if file exists
        if (class_exists('SRS_Loader')) {
            $this->loader = new SRS_Loader();
        } else {
            $all_files_present = false;
        }
        
        return $all_files_present;
    }

    /**
     * Display notice for missing files
     */
    private function display_missing_files_notice() {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong>Sports Registration System:</strong> 
                    The following required files are missing:
                </p>
                <ul style="margin-left: 20px; list-style-type: disc;">
                    <?php foreach ($this->missing_files as $file): ?>
                        <li><?php echo esc_html($file); ?></li>
                    <?php endforeach; ?>
                </ul>
                <p>
                    The plugin may not function correctly. Please reinstall the plugin.
                </p>
            </div>
            <?php
        });
    }

    /**
     * Set up internationalization
     */
    private function set_locale() {
        if (!isset($this->loader)) {
            return;
        }
        
        $plugin_i18n = new SRS_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register admin hooks
     */
    private function define_admin_hooks() {
        if (!isset($this->loader) || !class_exists('SRS_Admin')) {
            return;
        }
        
        $plugin_admin = new SRS_Admin($this->get_plugin_name(), $this->get_version());
        
        // Admin scripts and styles
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        // Admin menu
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // Settings
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
        
        // AJAX handlers for admin functions
        $this->loader->add_action('wp_ajax_srs_test_google_sheets', $plugin_admin, 'test_google_sheets_connection');
    }

    /**
     * Register public hooks
     */
    private function define_public_hooks() {
        if (!isset($this->loader) || !class_exists('SRS_Public')) {
            return;
        }
        
        $plugin_public = new SRS_Public($this->get_plugin_name(), $this->get_version());
        
        // Public scripts and styles
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Form processing
        $this->loader->add_action('wp_ajax_srs_submit_registration', $plugin_public, 'process_form_submission');
        $this->loader->add_action('wp_ajax_nopriv_srs_submit_registration', $plugin_public, 'process_form_submission');
        
        // Family account AJAX handlers
        $this->loader->add_action('wp_ajax_srs_update_family_profile', 'srs_update_family_profile_ajax');
        $this->loader->add_action('wp_ajax_srs_add_child', 'srs_add_child_ajax');
        $this->loader->add_action('wp_ajax_srs_update_child', 'srs_update_child_ajax');
        $this->loader->add_action('wp_ajax_srs_remove_child', 'srs_remove_child_ajax');
        $this->loader->add_action('wp_ajax_nopriv_srs_parent_login', 'srs_parent_login_ajax');
        $this->loader->add_action('wp_ajax_nopriv_srs_parent_register', 'srs_parent_register_ajax');
    }

    /**
     * Register Gutenberg blocks
     */
    private function register_blocks() {
        if (!function_exists('register_block_type')) {
            return;
        }
        
        add_action('init', array($this, 'register_gutenberg_blocks'));
    }

    /**
     * Register block types
     */
    public function register_gutenberg_blocks() {
        // Check if block editor assets exist
        if (!file_exists(SRS_PLUGIN_DIR . 'blocks/build/index.js')) {
            return;
        }
        
        wp_register_script(
            'srs-editor-script',
            SRS_PLUGIN_URL . 'blocks/build/index.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
            filemtime(SRS_PLUGIN_DIR . 'blocks/build/index.js')
        );

        wp_register_style(
            'srs-editor-style',
            SRS_PLUGIN_URL . 'blocks/build/editor.css',
            array('wp-edit-blocks'),
            filemtime(SRS_PLUGIN_DIR . 'blocks/build/editor.css')
        );

        wp_register_style(
            'srs-frontend-style',
            SRS_PLUGIN_URL . 'blocks/build/style.css',
            array(),
            filemtime(SRS_PLUGIN_DIR . 'blocks/build/style.css')
        );

        register_block_type('sports-registration/registration-form', array(
            'editor_script' => 'srs-editor-script',
            'editor_style' => 'srs-editor-style',
            'style' => 'srs-frontend-style',
            'render_callback' => array($this, 'render_registration_form_block'),
            'attributes' => array(
                'formType' =>
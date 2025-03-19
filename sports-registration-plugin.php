<?php
/**
 * Plugin Name: Sports Registration System
 * Plugin URI: https://yourwebsite.com/sports-registration
 * Description: Create registration forms for basketball, soccer, cheerleading, and volleyball with Square/PayPal integration and Google Sheets sync.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: sports-registration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SRS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SRS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SRS_PLUGIN_VERSION', '1.0.0');

// Include required files
require_once SRS_PLUGIN_DIR . 'includes/class-srs-activator.php';
require_once SRS_PLUGIN_DIR . 'includes/class-srs-deactivator.php';
require_once SRS_PLUGIN_DIR . 'includes/class-srs-loader.php';
require_once SRS_PLUGIN_DIR . 'includes/class-srs-i18n.php';
require_once SRS_PLUGIN_DIR . 'admin/class-srs-admin.php';
require_once SRS_PLUGIN_DIR . 'public/class-srs-public.php';
require_once SRS_PLUGIN_DIR . 'includes/class-srs-forms.php';
require_once SRS_PLUGIN_DIR . 'includes/class-srs-payments.php';
require_once SRS_PLUGIN_DIR . 'includes/class-srs-google-sheet.php';

/**
 * Begin execution of the plugin.
 */
function run_sports_registration_system() {
    register_activation_hook(__FILE__, array('SRS_Activator', 'activate'));
    register_deactivation_hook(__FILE__, array('SRS_Deactivator', 'deactivate'));
    
    $plugin = new SRS_Main();
    $plugin->run();
}

run_sports_registration_system();

/**
 * The main plugin class.
 */
class SRS_Main {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->plugin_name = 'sports-registration';
        $this->version = SRS_PLUGIN_VERSION;
        
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->register_blocks();
    }

    private function load_dependencies() {
        $this->loader = new SRS_Loader();
    }

    private function set_locale() {
        $plugin_i18n = new SRS_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    private function define_admin_hooks() {
        $plugin_admin = new SRS_Admin($this->get_plugin_name(), $this->get_version());
        
        // Admin scripts and styles
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        // Admin menu
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // Settings
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
    }

    private function define_public_hooks() {
        $plugin_public = new SRS_Public($this->get_plugin_name(), $this->get_version());
        
        // Public scripts and styles
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Form processing
        $this->loader->add_action('wp_ajax_srs_submit_registration', $plugin_public, 'process_form_submission');
        $this->loader->add_action('wp_ajax_nopriv_srs_submit_registration', $plugin_public, 'process_form_submission');
    }

    private function register_blocks() {
        // Register Gutenberg blocks
        if (function_exists('register_block_type')) {
            add_action('init', array($this, 'register_gutenberg_blocks'));
        }
    }

    public function register_gutenberg_blocks() {
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
                'formType' => array(
                    'type' => 'string',
                    'default' => 'basketball',
                ),
            ),
        ));
    }

    public function render_registration_form_block($attributes) {
        $forms = new SRS_Forms();
        return $forms->render_form($attributes['formType']);
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
}

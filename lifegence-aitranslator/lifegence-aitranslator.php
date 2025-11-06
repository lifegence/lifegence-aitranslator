<?php
/**
 * Plugin Name: Lifegence AITranslator
 * Plugin URI: https://lifegence.com
 * Description: AI-powered multilingual translation plugin using Gemini and OpenAI for WordPress websites.
 * Version: 1.0.0
 * Author: Lifegence Corporation
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: lifegence-aitranslator
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LG_AITRANS_VERSION', '1.0.0');
define('LG_AITRANS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LG_AITRANS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LG_AITRANS_PLUGIN_FILE', __FILE__);

/**
 * Main plugin class
 */
class LG_AITranslator {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Supported languages array
     */
    public static $languages = array(
        'en' => 'English',
        'ja' => '日本語',
        'zh-CN' => '简体中文',
        'zh-TW' => '繁體中文',
        'ko' => '한국어',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'ru' => 'Русский',
        'ar' => 'العربية',
        'hi' => 'हिन्दी',
        'th' => 'ไทย',
        'vi' => 'Tiếng Việt',
        'id' => 'Bahasa Indonesia',
        'tr' => 'Türkçe',
        'pl' => 'Polski',
        'nl' => 'Nederlands',
        'sv' => 'Svenska'
    );

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get all languages (preset + custom)
     *
     * @return array Associative array of language codes and names
     */
    public static function get_all_languages() {
        $preset = self::$languages;
        $custom = get_option('lg_aitranslator_custom_languages', array());

        // Merge custom languages (custom can override preset)
        return array_merge($preset, $custom);
    }

    /**
     * Get custom languages only
     *
     * @return array Associative array of custom language codes and names
     */
    public static function get_custom_languages() {
        return get_option('lg_aitranslator_custom_languages', array());
    }

    /**
     * Check if a language is a preset language
     *
     * @param string $code Language code
     * @return bool True if preset, false otherwise
     */
    public static function is_preset_language($code) {
        return isset(self::$languages[$code]);
    }

    /**
     * Validate language code format
     *
     * @param string $code Language code to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate_language_code($code) {
        // Allow alphanumeric, hyphen, and underscore
        // Examples: en, zh-CN, pt_BR
        return preg_match('/^[a-zA-Z0-9_-]+$/', $code) === 1 && !empty($code);
    }

    /**
     * Add a custom language
     *
     * @param string $code Language code
     * @param string $name Language name
     * @return bool True on success, false on failure
     */
    public static function add_custom_language($code, $name) {
        // Validate inputs
        if (!self::validate_language_code($code)) {
            return false;
        }

        $name = sanitize_text_field($name);
        if (empty(trim($name))) {
            return false;
        }

        // Get existing custom languages
        $custom = get_option('lg_aitranslator_custom_languages', array());

        // Add new language
        $custom[$code] = $name;

        // Save
        return update_option('lg_aitranslator_custom_languages', $custom);
    }

    /**
     * Remove a custom language
     *
     * @param string $code Language code to remove
     * @return bool True on success, false if not found
     */
    public static function remove_custom_language($code) {
        $custom = get_option('lg_aitranslator_custom_languages', array());

        if (!isset($custom[$code])) {
            return false;
        }

        unset($custom[$code]);

        return update_option('lg_aitranslator_custom_languages', $custom);
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Error handler (load first)
        require_once LG_AITRANS_PLUGIN_DIR . 'includes/class-error-handler.php';

        // Core classes
        require_once LG_AITRANS_PLUGIN_DIR . 'includes/class-translation-service-interface.php';
        require_once LG_AITRANS_PLUGIN_DIR . 'includes/class-abstract-translation-service.php';
        require_once LG_AITRANS_PLUGIN_DIR . 'includes/class-translation-service-factory.php';
        require_once LG_AITRANS_PLUGIN_DIR . 'includes/class-gemini-translation-service.php';
        require_once LG_AITRANS_PLUGIN_DIR . 'includes/class-openai-translation-service.php';
        require_once LG_AITRANS_PLUGIN_DIR . 'includes/class-translation-cache.php';
        require_once LG_AITRANS_PLUGIN_DIR . 'includes/class-api-key-manager.php';

        // URL rewriting and translation
        require_once LG_AITRANS_PLUGIN_DIR . 'includes/class-url-rewriter.php';
        require_once LG_AITRANS_PLUGIN_DIR . 'includes/class-content-translator.php';

        // Admin classes
        if (is_admin()) {
            require_once LG_AITRANS_PLUGIN_DIR . 'admin/class-admin-settings.php';
            require_once LG_AITRANS_PLUGIN_DIR . 'admin/class-admin-ajax.php';
        }

        // Widget
        require_once LG_AITRANS_PLUGIN_DIR . 'includes/class-language-switcher-widget.php';
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Initialize URL rewriter and content translator
        add_action('init', array($this, 'init_translation_system'), 5);

        // Register rewrite rules
        add_action('init', array($this, 'add_rewrite_rules'), 1);

        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_notices', array('LG_Error_Handler', 'display_admin_notices'));

        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('widgets_init', array($this, 'register_widgets'));

        // Shortcodes
        add_shortcode('lg-translator', array($this, 'language_switcher_shortcode'));

        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    /**
     * Initialize translation system
     */
    public function init_translation_system() {
        // Initialize URL rewriter (must be early)
        new LG_URL_Rewriter();

        // Initialize content translator
        new LG_Content_Translator();
    }

    /**
     * Add rewrite rules for language URLs
     */
    public function add_rewrite_rules() {
        $settings = get_option('lg_aitranslator_settings', array());
        $languages = $settings['supported_languages'] ?? array('en', 'ja', 'zh-CN', 'es', 'fr');

        // Create regex pattern for supported languages
        $lang_pattern = implode('|', array_map('preg_quote', $languages));

        // Add rewrite rule for language prefix URLs
        // This ensures /ja/anything gets routed to WordPress
        add_rewrite_rule(
            '^(' . $lang_pattern . ')(/(.*))?/?$',
            'index.php?lang=$matches[1]&lg_translated_path=$matches[3]',
            'top'
        );

        // Register query vars
        add_filter('query_vars', function($vars) {
            $vars[] = 'lang';
            $vars[] = 'lg_translated_path';
            return $vars;
        });
    }

    /**
     * Plugin activation
     */
    public function activate() {
        $default_settings = array(
            'enabled' => true,
            'provider' => 'gemini',
            'model' => 'gemini-2.5-flash',
            'default_language' => 'en',
            'supported_languages' => array('en', 'ja', 'zh-CN', 'es', 'fr'),
            'cache_enabled' => true,
            'cache_ttl' => 86400,
            'cache_backend' => 'transients',
            'translation_quality' => 'standard',
            'rate_limit_enabled' => true,
            'rate_limit_per_hour' => 1000,
            'monthly_budget_limit' => 50,
            'auto_disable_on_budget' => false
        );

        add_option('lg_aitranslator_settings', $default_settings);
        add_option('lg_aitranslator_version', LG_AITRANS_VERSION);
        add_option('lg_aitranslator_cache_version', 1);

        // Flush rewrite rules to ensure language URLs work properly
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules to remove language URL patterns
        flush_rewrite_rules();
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Lifegence AITranslator Settings', 'lifegence-aitranslator'),
            __('Lifegence AITranslator', 'lifegence-aitranslator'),
            'manage_options',
            'lifegence-aitranslator',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Render admin settings page
     */
    public function render_admin_page() {
        if (!class_exists('LG_AITranslator_Admin_Settings')) {
            return;
        }

        $admin_settings = new LG_AITranslator_Admin_Settings();
        $admin_settings->render();
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_lifegence-aitranslator') {
            return;
        }

        wp_enqueue_style(
            'lifegence-aitranslator-admin',
            LG_AITRANS_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            LG_AITRANS_VERSION
        );

        wp_enqueue_script(
            'lifegence-aitranslator-admin',
            LG_AITRANS_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            LG_AITRANS_VERSION,
            true
        );

        wp_localize_script('lifegence-aitranslator-admin', 'lgAITranslator', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lg_aitranslator_admin'),
            'strings' => array(
                'testing' => __('Testing...', 'lifegence-aitranslator'),
                'clearing' => __('Clearing...', 'lifegence-aitranslator'),
                'success' => __('Success', 'lifegence-aitranslator'),
                'error' => __('Error', 'lifegence-aitranslator')
            )
        ));
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        $settings = get_option('lg_aitranslator_settings', array());

        if (empty($settings['enabled'])) {
            return;
        }

        wp_enqueue_style(
            'lifegence-aitranslator-frontend',
            LG_AITRANS_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            LG_AITRANS_VERSION
        );

        wp_enqueue_script(
            'lifegence-aitranslator-frontend',
            LG_AITRANS_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            LG_AITRANS_VERSION,
            true
        );

        wp_localize_script('lifegence-aitranslator-frontend', 'lgAITranslatorFrontend', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'resturl' => rest_url('lifegence-aitranslator/v1/'),
            'nonce' => wp_create_nonce('lg_aitranslator_frontend'),
            'currentLang' => $this->get_current_language(),
            'defaultLang' => $settings['default_language'] ?? 'en'
        ));
    }

    /**
     * Register widgets
     */
    public function register_widgets() {
        register_widget('LG_Language_Switcher_Widget');
    }

    /**
     * Language switcher shortcode
     */
    public function language_switcher_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'dropdown',
            'flags' => 'yes',
            'native_names' => 'yes'
        ), $atts);

        $widget = new LG_Language_Switcher_Widget();
        return $widget->render_switcher($atts);
    }

    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('lifegence-aitranslator/v1', '/translate', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_translate'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'text' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'target_lang' => array(
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'source_lang' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));

        register_rest_route('lifegence-aitranslator/v1', '/languages', array(
            'methods' => 'GET',
            'callback' => array($this, 'rest_get_languages'),
            'permission_callback' => '__return_true' // Public endpoint
        ));
    }

    /**
     * Check REST API permission
     */
    public function check_api_permission($request) {
        // Allow logged-in users or verify nonce for public access
        if (is_user_logged_in()) {
            return true;
        }

        // Verify nonce for public API access
        $nonce = $request->get_header('X-WP-Nonce');
        if ($nonce && wp_verify_nonce($nonce, 'wp_rest')) {
            return true;
        }

        return new WP_Error(
            'rest_forbidden',
            __('You do not have permission to access this endpoint.', 'lifegence-aitranslator'),
            array('status' => 403)
        );
    }

    /**
     * REST API: Translate text
     */
    public function rest_translate($request) {
        $settings = get_option('lg_aitranslator_settings', array());

        if (empty($settings['enabled'])) {
            return new WP_Error('disabled', __('Translation service is disabled', 'lifegence-aitranslator'), array('status' => 403));
        }

        $text = $request->get_param('text');
        $target_lang = $request->get_param('target_lang');
        $source_lang = $request->get_param('source_lang') ?: $settings['default_language'];

        if (empty($text) || empty($target_lang)) {
            return new WP_Error('missing_params', __('Missing required parameters', 'lifegence-aitranslator'), array('status' => 400));
        }

        try {
            $service = LG_Translation_Service_Factory::create();
            $translation = $service->translate_text($text, $source_lang, $target_lang);

            return array(
                'success' => true,
                'translation' => $translation,
                'source_lang' => $source_lang,
                'target_lang' => $target_lang
            );
        } catch (Exception $e) {
            return new WP_Error('translation_failed', $e->getMessage(), array('status' => 500));
        }
    }

    /**
     * REST API: Get supported languages
     */
    public function rest_get_languages($request) {
        $settings = get_option('lg_aitranslator_settings', array());
        $supported = $settings['supported_languages'] ?? array();

        $languages = array();
        foreach ($supported as $code) {
            if (isset(self::$languages[$code])) {
                $languages[$code] = self::$languages[$code];
            }
        }

        return array(
            'languages' => $languages,
            'default' => $settings['default_language'] ?? 'en',
            'current' => $this->get_current_language()
        );
    }

    /**
     * Get current language
     */
    private function get_current_language() {
        $settings = get_option('lg_aitranslator_settings', array());
        $default = $settings['default_language'] ?? 'en';

        // Check cookie
        if (isset($_COOKIE['lg_aitranslator_lang'])) {
            return sanitize_text_field(wp_unslash($_COOKIE['lg_aitranslator_lang']));
        }

        // Check query parameter
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Public query parameter for language selection
        if (isset($_GET['lang'])) {
            return sanitize_text_field(wp_unslash($_GET['lang']));
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        return $default;
    }
}

// Initialize the plugin
function lg_aitranslator() {
    return LG_AITranslator::get_instance();
}

// Start the plugin
lg_aitranslator();

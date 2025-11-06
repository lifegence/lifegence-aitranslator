<?php
/**
 * URL Rewriter for Language Prefix Support
 *
 * @package LG_AITranslator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle URL rewriting and language detection
 */
class LG_URL_Rewriter {

    /**
     * Current language code
     */
    private $current_language = null;

    /**
     * Default language code
     */
    private $default_language = null;

    /**
     * Supported languages
     */
    private $supported_languages = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_settings();
        $this->init_hooks();
    }

    /**
     * Initialize settings
     */
    private function init_settings() {
        $settings = get_option('lg_aitranslator_settings', array());
        $this->default_language = $settings['default_language'] ?? 'en';
        $this->supported_languages = $settings['supported_languages'] ?? array('en');
    }

    /**
     * Initialize WordPress hooks
     */
    public function init_hooks() {
        // Process language prefix immediately - this must run BEFORE WordPress parses the request
        $this->process_language_prefix();

        // Detect language early
        add_action('init', array($this, 'detect_language'), 5);

        // Don't filter URLs - causes redirect loops
        // Language switching will be handled by frontend JavaScript and widgets

        // Don't handle legacy redirects - .htaccess already adds ?lang= parameter
        // which would cause infinite redirect loop
    }

    /**
     * Process language prefix from URL
     * This runs very early to modify REQUEST_URI before WordPress processes it
     */
    public function process_language_prefix() {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $path = wp_parse_url($request_uri, PHP_URL_PATH);

        // Debug logging
        LG_Error_Handler::debug('URL Rewriter - Original REQUEST_URI', array('uri' => $request_uri, 'path' => $path));

        // Check if path starts with a language prefix
        $language_pattern = $this->get_language_pattern();
        LG_Error_Handler::debug('URL Rewriter - Language pattern', array('pattern' => $language_pattern));

        if (preg_match('#^/(' . $language_pattern . ')(/|$)#', $path, $matches)) {
            $detected_lang = $matches[1];
            LG_Error_Handler::debug('URL Rewriter - Detected language', array('lang' => $detected_lang));

            // Validate language
            if (in_array($detected_lang, $this->supported_languages, true)) {
                // Store detected language globally so other instances can access it
                $GLOBALS['lg_aitranslator_current_lang'] = $detected_lang;
                $this->current_language = $detected_lang;

                LG_Error_Handler::debug('URL Rewriter - Language validated', array('lang' => $detected_lang));

                // Remove language prefix from REQUEST_URI
                $new_path = preg_replace('#^/' . preg_quote($detected_lang, '#') . '(/|$)#', '/', $path);

                // Update REQUEST_URI to point to the original path
                $query = wp_parse_url($request_uri, PHP_URL_QUERY);
                $new_request_uri = $new_path . ($query ? '?' . $query : '');
                $_SERVER['REQUEST_URI'] = $new_request_uri;
                LG_Error_Handler::debug('URL Rewriter - Updated REQUEST_URI', array('new_uri' => $new_request_uri));
            } else {
                LG_Error_Handler::debug('URL Rewriter - Language not supported', array('lang' => $detected_lang));
            }
        } else {
            LG_Error_Handler::debug('URL Rewriter - No language prefix detected');
        }
    }

    /**
     * Get regex pattern for supported languages
     */
    private function get_language_pattern() {
        $languages = $this->supported_languages;

        // Escape special characters and sort by length (longest first)
        usort($languages, function($a, $b) {
            return strlen($b) - strlen($a);
        });

        $escaped = array_map('preg_quote', $languages);
        return implode('|', $escaped);
    }

    /**
     * Detect language from URL
     */
    public function detect_language() {
        // Already detected
        if ($this->current_language !== null) {
            return $this->current_language;
        }

        // Check global variable set by process_language_prefix()
        if (isset($GLOBALS['lg_aitranslator_current_lang'])) {
            $this->current_language = $GLOBALS['lg_aitranslator_current_lang'];
            return $this->current_language;
        }

        // Check URL query var (set by .htaccess rewrite)
        $lang = get_query_var('lang');

        if (!empty($lang) && in_array($lang, $this->supported_languages)) {
            $this->current_language = $lang;
            return $this->current_language;
        }

        // Check URL path directly
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $path = wp_parse_url($request_uri, PHP_URL_PATH);

        if (preg_match('#^/(' . $this->get_language_pattern() . ')(/|$)#', $path, $matches)) {
            $detected_lang = $matches[1];
            if (in_array($detected_lang, $this->supported_languages, true)) {
                $this->current_language = $detected_lang;
                return $this->current_language;
            }
        }

        // Check cookie as fallback
        if (isset($_COOKIE['lg_aitranslator_lang'])) {
            $cookie_lang = sanitize_text_field(wp_unslash($_COOKIE['lg_aitranslator_lang']));
            if (in_array($cookie_lang, $this->supported_languages, true)) {
                $this->current_language = $cookie_lang;
                return $this->current_language;
            }
        }

        // Default language
        $this->current_language = $this->default_language;
        return $this->current_language;
    }

    /**
     * Get current language
     */
    public function get_current_language() {
        if ($this->current_language === null) {
            $this->detect_language();
        }
        return $this->current_language;
    }

    /**
     * Get default language
     */
    public function get_default_language() {
        return $this->default_language;
    }

    /**
     * Filter URLs to add language prefix
     */
    public function filter_url($url, $path = '') {
        // Don't filter admin URLs
        if (is_admin() || strpos($url, '/wp-admin/') !== false || strpos($url, '/wp-content/') !== false) {
            return $url;
        }

        $current_lang = $this->get_current_language();

        // Don't add prefix for default language
        if ($current_lang === $this->default_language) {
            return $url;
        }

        // Add language prefix
        return $this->add_language_prefix($url, $current_lang);
    }

    /**
     * Add language prefix to URL
     */
    public function add_language_prefix($url, $lang) {
        // Skip if language is default
        if ($lang === $this->default_language) {
            return $url;
        }

        // Parse URL
        $parsed = wp_parse_url($url);
        $path = $parsed['path'] ?? '/';

        // Check if language prefix already exists
        if (preg_match('#^/(' . $this->get_language_pattern() . ')(/|$)#', $path)) {
            return $url;
        }

        // Add language prefix
        $path = '/' . $lang . $path;

        // Rebuild URL
        $parsed['path'] = $path;
        return $this->build_url($parsed);
    }

    /**
     * Remove language prefix from URL
     */
    public function remove_language_prefix($url) {
        $parsed = wp_parse_url($url);
        $path = $parsed['path'] ?? '/';

        // Remove language prefix
        $path = preg_replace('#^/(' . $this->get_language_pattern() . ')(/|$)#', '/', $path);

        // Rebuild URL
        $parsed['path'] = $path;
        return $this->build_url($parsed);
    }

    /**
     * Build URL from parsed components
     */
    private function build_url($parsed) {
        $scheme   = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
        $host     = isset($parsed['host']) ? $parsed['host'] : '';
        $port     = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $user     = isset($parsed['user']) ? $parsed['user'] : '';
        $pass     = isset($parsed['pass']) ? ':' . $parsed['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed['path']) ? $parsed['path'] : '';
        $query    = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * Handle legacy ?lang= parameter redirects
     */
    public function handle_legacy_redirect() {
        // Check if old ?lang= parameter is used
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Public query parameter for language selection
        if (isset($_GET['lang']) && !empty($_GET['lang'])) {
            $lang = sanitize_text_field(wp_unslash($_GET['lang']));
            // phpcs:enable WordPress.Security.NonceVerification.Recommended

            // Validate language
            if (!in_array($lang, $this->supported_languages, true)) {
                return;
            }

            // Build new URL with language prefix
            $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
            $protocol = $is_https ? 'https' : 'http';
            $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
            $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
            $current_url = $protocol . '://' . $host . $request_uri;

            // Remove ?lang= parameter
            $new_url = remove_query_arg('lang', $current_url);

            // Add language prefix if not default
            if ($lang !== $this->default_language) {
                $parsed = wp_parse_url($new_url);
                $parsed['path'] = '/' . $lang . ($parsed['path'] ?? '/');
                $new_url = $this->build_url($parsed);
            }

            // 301 redirect to new URL
            wp_redirect($new_url, 301);
            exit;
        }
    }

    /**
     * Get language-specific URL for current page
     */
    public function get_language_url($lang) {
        $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $protocol = $is_https ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $current_url = $protocol . '://' . $host . $request_uri;

        // Remove existing language prefix
        $url = $this->remove_language_prefix($current_url);

        // Add new language prefix
        if ($lang !== $this->default_language) {
            $url = $this->add_language_prefix($url, $lang);
        }

        return $url;
    }

}

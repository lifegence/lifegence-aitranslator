<?php
/**
 * Content Translator
 *
 * @package LG_AITranslator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Translate WordPress content using AI
 */
class LG_Content_Translator {

    /**
     * Minimum translatable text length
     */
    const MIN_TEXT_LENGTH = 2;

    /**
     * Minimum HTML length to process
     */
    const MIN_HTML_LENGTH = 100;

    /**
     * Maximum sample texts to log
     */
    const MAX_LOG_SAMPLES = 5;

    /**
     * URL Rewriter instance
     */
    private $url_rewriter;

    /**
     * Translation service
     */
    private $translation_service;

    /**
     * Cache instance
     */
    private $cache;

    /**
     * Settings
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct() {
        $this->url_rewriter = new LG_URL_Rewriter();
        $this->cache = new LG_Translation_Cache();
        $this->settings = get_option('lg_aitranslator_settings', array());

        $this->init_translation_service();
        $this->init_hooks();
    }

    /**
     * Initialize translation service
     */
    private function init_translation_service() {
        $this->translation_service = LG_Translation_Service_Factory::create();
    }

    /**
     * Initialize WordPress hooks
     */
    public function init_hooks() {
        // Only hook if translation is enabled
        if (empty($this->settings['enabled'])) {
            return;
        }

        // HTML output buffering for full page translation
        add_action('template_redirect', array($this, 'start_output_buffer'), 1);

        // Content filters - use high priority to run after other plugins
        add_filter('the_title', array($this, 'translate_title'), 999, 2);
        add_filter('the_content', array($this, 'translate_content'), 999);
        add_filter('the_excerpt', array($this, 'translate_excerpt'), 999, 2);

        // Widget filters
        add_filter('widget_title', array($this, 'translate_widget_title'), 999, 3);
        add_filter('widget_text', array($this, 'translate_widget_text'), 999, 3);

        // Menu filters
        add_filter('wp_nav_menu_items', array($this, 'translate_menu_items'), 999, 2);

        // Category/Tag names
        add_filter('single_cat_title', array($this, 'translate_term_name'), 999);
        add_filter('single_tag_title', array($this, 'translate_term_name'), 999);

        // SEO hooks
        add_action('wp_head', array($this, 'output_hreflang_tags'));
        add_filter('language_attributes', array($this, 'filter_language_attributes'));

        // Admin bar menu for edit mode
        add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 100);
    }

    /**
     * Check if translation edit mode is enabled
     */
    private function is_edit_mode() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public query parameter for edit mode, permission checked with current_user_can
        return isset($_GET['lg_aitrans_edit']) && sanitize_text_field(wp_unslash($_GET['lg_aitrans_edit'])) === '1' && current_user_can('manage_options');
    }

    /**
     * Start output buffering for full page translation
     */
    public function start_output_buffer() {
        // Skip for admin pages
        if (is_admin()) {
            return;
        }

        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();

        // Debug: Log language detection
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        LG_Error_Handler::debug('Output buffer started', array(
            'current_lang' => $current_lang,
            'global_var' => isset($GLOBALS['lg_aitranslator_current_lang']) ? $GLOBALS['lg_aitranslator_current_lang'] : 'NOT SET',
            'request_uri' => $request_uri,
            'query_var_lang' => get_query_var('lang')
        ));
        $default_lang = $this->url_rewriter->get_default_language();

        // Only buffer if not default language
        if ($current_lang !== $default_lang) {
            ob_start(array($this, 'translate_html_output'));
        }
    }

    /**
     * Translate entire HTML output
     */
    public function translate_html_output($html) {
        // Skip empty output
        if (empty($html)) {
            return $html;
        }

        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();
        $default_lang = $this->url_rewriter->get_default_language();

        // Skip if default language
        if ($current_lang === $default_lang) {
            return $html;
        }

        // Generate cache key for entire page
        $cache_key = 'page_' . md5($html) . '_' . $current_lang;

        // Check cache
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Translate text nodes in HTML
        $translated_html = $this->translate_html_text_nodes($html, $current_lang);

        // Add language prefix to internal links
        $translated_html = $this->add_language_prefix_to_links($translated_html, $current_lang);

        // Cache result
        $this->cache->set($cache_key, $translated_html);

        return $translated_html;
    }

    /**
     * Add language prefix to all internal links in HTML
     */
    private function add_language_prefix_to_links($html, $current_lang) {
        $default_lang = $this->url_rewriter->get_default_language();

        // Skip if default language
        if ($current_lang === $default_lang) {
            return $html;
        }

        // Get site URL
        $site_url = home_url();
        $site_domain = wp_parse_url($site_url, PHP_URL_HOST);

        // Pattern to match href attributes
        $pattern = '/href=["\']([^"\']+)["\']/i';

        $html = preg_replace_callback($pattern, function($matches) use ($current_lang, $site_url, $site_domain) {
            $url = $matches[1];

            // Skip external links
            if (strpos($url, 'http') === 0 && strpos($url, $site_domain) === false) {
                return $matches[0];
            }

            // Skip anchors, mailto, tel, javascript
            if (strpos($url, '#') === 0 ||
                strpos($url, 'mailto:') === 0 ||
                strpos($url, 'tel:') === 0 ||
                strpos($url, 'javascript:') === 0) {
                return $matches[0];
            }

            // Skip admin URLs
            if (strpos($url, '/wp-admin') !== false ||
                strpos($url, '/wp-content') !== false ||
                strpos($url, '/wp-includes') !== false) {
                return $matches[0];
            }

            // Check if URL already has language prefix
            $supported_languages = $this->settings['supported_languages'] ?? array();
            $lang_pattern = '/^\/(' . implode('|', array_map('preg_quote', $supported_languages)) . ')\//';

            if (preg_match($lang_pattern, $url)) {
                return $matches[0]; // Already has language prefix
            }

            // Add language prefix to relative URLs
            if (strpos($url, '/') === 0) {
                $new_url = '/' . $current_lang . $url;
                return 'href="' . $new_url . '"';
            }

            // Add language prefix to absolute site URLs
            if (strpos($url, $site_url) === 0) {
                $path = str_replace($site_url, '', $url);
                $new_url = $site_url . '/' . $current_lang . $path;
                return 'href="' . $new_url . '"';
            }

            // Return unchanged for other cases
            return $matches[0];
        }, $html);

        return $html;
    }

    /**
     * Translate text nodes in HTML while preserving structure
     */
    private function translate_html_text_nodes($html, $target_lang) {
        // Don't translate if HTML is too small (likely API response)
        if (strlen($html) < self::MIN_HTML_LENGTH) {
            return $html;
        }

        LG_Error_Handler::debug('Starting HTML translation', array(
            'target_lang' => $target_lang,
            'html_length' => strlen($html)
        ));

        // Remove script and style tags completely before processing
        $html_without_scripts = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '<!--SCRIPT_REMOVED-->', $html);
        $html_without_scripts = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '<!--STYLE_REMOVED-->', $html_without_scripts);

        LG_Error_Handler::debug('HTML cleaned', array(
            'cleaned_length' => strlen($html_without_scripts)
        ));

        // Extract text nodes only from visible content
        // Pattern: text between tags that contains actual letters
        $pattern = '/>([^<]+)</';
        $text_nodes = array();
        $placeholders = array();

        preg_match_all($pattern, $html_without_scripts, $matches);

        LG_Error_Handler::debug('Text nodes found', array(
            'potential_nodes' => count($matches[1])
        ));

        $extracted_count = 0;
        $samples = array();

        foreach ($matches[1] as $text) {
            $trimmed = trim($text);

            // Skip empty or whitespace-only
            if ($trimmed === '') {
                continue;
            }

            // Skip if no letters (numbers, symbols only)
            if (!preg_match('/\p{L}/u', $trimmed)) {
                continue;
            }

            // Skip very short text (likely noise)
            if (mb_strlen($trimmed) < self::MIN_TEXT_LENGTH) {
                continue;
            }

            // Skip URLs and emails
            if (preg_match('/^https?:\/\//i', $trimmed) || filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            // Skip CSS-like content
            if (preg_match('/^[\{\}:;]|display:|margin:|padding:|color:/i', $trimmed)) {
                continue;
            }

            $extracted_count++;
            if ($extracted_count <= self::MAX_LOG_SAMPLES) {
                $samples[] = substr($trimmed, 0, 80);
            }

            // Check individual text cache
            $cache_key = 'text_' . md5($text) . '_' . $target_lang;
            $cached = $this->cache->get($cache_key);

            if ($cached !== false) {
                // Use cached translation
                $placeholders[$text] = $cached;
            } else {
                // Need to translate
                $text_nodes[] = $text;
            }
        }

        LG_Error_Handler::debug('Text extraction completed', array(
            'extracted_count' => $extracted_count,
            'need_translation' => count($text_nodes),
            'samples' => $samples
        ));

        // Batch translate all uncached text nodes
        if (!empty($text_nodes)) {
            LG_Error_Handler::debug('Starting batch translation', array(
                'text_count' => count($text_nodes)
            ));

            $batch_translations = $this->batch_translate_texts($text_nodes, $target_lang);

            LG_Error_Handler::debug('Batch translation completed', array(
                'results_count' => count($batch_translations)
            ));

            // Cache and merge results
            foreach ($batch_translations as $original => $translated) {
                $placeholders[$original] = $translated;

                // Cache individual texts
                $cache_key = 'text_' . md5($original) . '_' . $target_lang;
                $this->cache->set($cache_key, $translated);
            }
        }

        // Replace all text nodes with translations in the ORIGINAL HTML (not cleaned)
        if (!empty($placeholders)) {
            LG_Error_Handler::debug('Replacing text nodes with translations', array(
                'placeholder_count' => count($placeholders)
            ));

            $edit_mode = $this->is_edit_mode();
            $text_index = 0;

            foreach ($placeholders as $original => $translated) {
                if ($edit_mode) {
                    // Add edit button in edit mode
                    $cache_key = 'text_' . md5($original) . '_' . $target_lang;
                    $edit_html = '<span class="lg-aitrans-editable" data-original="' . esc_attr($original) . '" data-cache-key="' . esc_attr($cache_key) . '" data-lang="' . esc_attr($target_lang) . '">'
                               . $translated
                               . '<button class="lg-aitrans-edit-btn" data-index="' . $text_index . '">✏️</button></span>';
                    $html = str_replace('>' . $original . '<', '>' . $edit_html . '<', $html);
                    $text_index++;
                } else {
                    $html = str_replace('>' . $original . '<', '>' . $translated . '<', $html);
                }
            }
        }

        LG_Error_Handler::debug('HTML translation completed');
        return $html;
    }

    /**
     * Batch translate multiple texts in a single API call
     */
    private function batch_translate_texts($texts, $target_lang) {
        if (empty($texts)) {
            return array();
        }

        // Translate ALL texts in a SINGLE API call to avoid rate limits
        // This uses only 1 API request per page instead of multiple
        LG_Error_Handler::debug('Batch translating texts in single API call', array(
            'text_count' => count($texts)
        ));

        $results = $this->batch_translate_chunk($texts, $target_lang);

        LG_Error_Handler::debug('Batch translation returned results', array(
            'results_count' => count($results)
        ));

        return $results;
    }

    /**
     * Translate a chunk of texts
     */
    private function batch_translate_chunk($texts, $target_lang) {
        if (empty($texts)) {
            return array();
        }

        // Combine texts with delimiters
        $delimiter = "\n###TRANSLATE_SPLIT###\n";
        $combined_text = implode($delimiter, $texts);

        // Translate the batch
        try {
            $translated_combined = $this->translate_text($combined_text, $target_lang);

            // Split back into individual translations
            $translated_parts = explode($delimiter, $translated_combined);

            // Map originals to translations
            $results = array();
            foreach ($texts as $index => $original) {
                $results[$original] = isset($translated_parts[$index]) ? trim($translated_parts[$index]) : $original;
            }

            return $results;
        } catch (Exception $e) {
            LG_Error_Handler::handle_exception($e, 'Batch translation failed');

            // Return originals on error
            $results = array();
            foreach ($texts as $original) {
                $results[$original] = $original;
            }
            return $results;
        }
    }

    /**
     * Translate post/page title
     */
    public function translate_title($title, $post_id = 0) {
        // Skip empty titles
        if (empty($title)) {
            return $title;
        }

        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();
        $default_lang = $this->url_rewriter->get_default_language();

        // Skip if default language
        if ($current_lang === $default_lang) {
            return $title;
        }

        // Generate cache key
        $cache_key = $this->generate_cache_key('post_title', $post_id, $current_lang, $title);

        // Check cache
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Translate
        $translated = $this->translate_text($title, $current_lang);

        // Cache result
        $this->cache->set($cache_key, $translated);

        return $translated;
    }

    /**
     * Translate post/page content
     */
    public function translate_content($content) {
        // Skip empty content
        if (empty($content)) {
            return $content;
        }

        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();
        $default_lang = $this->url_rewriter->get_default_language();

        // Skip if default language
        if ($current_lang === $default_lang) {
            return $content;
        }

        // Get post ID
        $post_id = get_the_ID();

        // Generate cache key
        $cache_key = $this->generate_cache_key('post_content', $post_id, $current_lang, $content);

        // Check cache
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Translate (preserve HTML)
        $translated = $this->translate_html($content, $current_lang);

        // Cache result
        $this->cache->set($cache_key, $translated);

        return $translated;
    }

    /**
     * Translate post excerpt
     */
    public function translate_excerpt($excerpt, $post_id = 0) {
        // Skip empty excerpts
        if (empty($excerpt)) {
            return $excerpt;
        }

        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();
        $default_lang = $this->url_rewriter->get_default_language();

        // Skip if default language
        if ($current_lang === $default_lang) {
            return $excerpt;
        }

        // Generate cache key
        $cache_key = $this->generate_cache_key('post_excerpt', $post_id, $current_lang, $excerpt);

        // Check cache
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Translate
        $translated = $this->translate_text($excerpt, $current_lang);

        // Cache result
        $this->cache->set($cache_key, $translated);

        return $translated;
    }

    /**
     * Translate widget title
     */
    public function translate_widget_title($title, $instance = array(), $widget_id = '') {
        // Skip empty titles
        if (empty($title)) {
            return $title;
        }

        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();
        $default_lang = $this->url_rewriter->get_default_language();

        // Skip if default language
        if ($current_lang === $default_lang) {
            return $title;
        }

        // Generate cache key
        $cache_key = $this->generate_cache_key('widget_title', $widget_id, $current_lang, $title);

        // Check cache
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Translate
        $translated = $this->translate_text($title, $current_lang);

        // Cache result
        $this->cache->set($cache_key, $translated);

        return $translated;
    }

    /**
     * Translate widget text
     */
    public function translate_widget_text($text, $instance = array(), $widget_id = '') {
        // Skip empty text
        if (empty($text)) {
            return $text;
        }

        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();
        $default_lang = $this->url_rewriter->get_default_language();

        // Skip if default language
        if ($current_lang === $default_lang) {
            return $text;
        }

        // Generate cache key
        $cache_key = $this->generate_cache_key('widget_text', $widget_id, $current_lang, $text);

        // Check cache
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Translate (preserve HTML if present)
        $translated = $this->translate_html($text, $current_lang);

        // Cache result
        $this->cache->set($cache_key, $translated);

        return $translated;
    }

    /**
     * Translate menu items
     */
    public function translate_menu_items($items, $args) {
        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();
        $default_lang = $this->url_rewriter->get_default_language();

        // Skip if default language
        if ($current_lang === $default_lang) {
            return $items;
        }

        // Translate menu item text
        $items = preg_replace_callback('/>([^<]+)<\/a>/', function($matches) use ($current_lang) {
            $text = $matches[1];

            // Generate cache key
            $cache_key = $this->generate_cache_key('menu_item', 0, $current_lang, $text);

            // Check cache
            $cached = $this->cache->get($cache_key);
            if ($cached !== false) {
                return '>' . $cached . '</a>';
            }

            // Translate
            $translated = $this->translate_text($text, $current_lang);

            // Cache result
            $this->cache->set($cache_key, $translated);

            return '>' . $translated . '</a>';
        }, $items);

        return $items;
    }

    /**
     * Translate term name
     */
    public function translate_term_name($name) {
        // Skip empty names
        if (empty($name)) {
            return $name;
        }

        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();
        $default_lang = $this->url_rewriter->get_default_language();

        // Skip if default language
        if ($current_lang === $default_lang) {
            return $name;
        }

        // Generate cache key
        $cache_key = $this->generate_cache_key('term_name', 0, $current_lang, $name);

        // Check cache
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        // Translate
        $translated = $this->translate_text($name, $current_lang);

        // Cache result
        $this->cache->set($cache_key, $translated);

        return $translated;
    }

    /**
     * Translate plain text
     */
    private function translate_text($text, $target_lang) {
        // Generate cache key
        $cache_key = 'text_' . md5($text) . '_' . $target_lang;

        // Check cache
        $cached = $this->cache->get($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        try {
            $default_lang = $this->url_rewriter->get_default_language();
            $translated = $this->translation_service->translate_text($text, $default_lang, $target_lang);

            // Cache result
            $this->cache->set($cache_key, $translated);

            return $translated;
        } catch (Exception $e) {
            LG_Error_Handler::handle_exception($e, 'Translation failed');
            return $text; // Return original on error
        }
    }

    /**
     * Translate HTML content (preserves structure)
     */
    private function translate_html($html, $target_lang) {
        try {
            $default_lang = $this->url_rewriter->get_default_language();
            return $this->translation_service->translate_html($html, $default_lang, $target_lang);
        } catch (Exception $e) {
            LG_Error_Handler::handle_exception($e, 'HTML translation failed');
            return $html; // Return original on error
        }
    }

    /**
     * Generate cache key (unified with translation services)
     */
    private function generate_cache_key($type, $id, $lang, $content) {
        $cache_version = get_option('lg_aitranslator_cache_version', 1);
        $hash = md5($content . $cache_version);
        return "{$type}_{$hash}_{$lang}";
    }

    /**
     * Output hreflang tags for SEO
     */
    public function output_hreflang_tags() {
        $supported_languages = $this->settings['supported_languages'] ?? array();
        $default_lang = $this->url_rewriter->get_default_language();

        foreach ($supported_languages as $lang) {
            $url = $this->url_rewriter->get_language_url($lang);
            echo '<link rel="alternate" hreflang="' . esc_attr($lang) . '" href="' . esc_url($url) . '" />' . "\n";
        }

        // x-default for default language
        $default_url = $this->url_rewriter->get_language_url($default_lang);
        echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($default_url) . '" />' . "\n";
    }

    /**
     * Filter HTML language attribute
     */
    public function filter_language_attributes($output) {
        $current_lang = $this->url_rewriter->get_current_language();

        // Convert language code to locale format
        $locale_map = array(
            'en' => 'en_US',
            'ja' => 'ja_JP',
            'zh-CN' => 'zh_CN',
            'zh-TW' => 'zh_TW',
            'ko' => 'ko_KR',
            'es' => 'es_ES',
            'fr' => 'fr_FR',
            'de' => 'de_DE',
            'it' => 'it_IT',
            'pt' => 'pt_PT',
            'ru' => 'ru_RU',
            'ar' => 'ar',
            'hi' => 'hi_IN',
            'th' => 'th_TH',
            'vi' => 'vi_VN',
            'id' => 'id_ID',
            'tr' => 'tr_TR',
            'pl' => 'pl_PL',
            'nl' => 'nl_NL',
            'sv' => 'sv_SE'
        );

        $locale = $locale_map[$current_lang] ?? $current_lang;

        return str_replace('lang="en-US"', 'lang="' . esc_attr($current_lang) . '"', $output);
    }

    /**
     * Add admin bar menu for translation edit mode
     */
    public function add_admin_bar_menu($wp_admin_bar) {
        // Only show for admins on frontend pages
        if (!current_user_can('manage_options') || is_admin()) {
            return;
        }

        // Get current language
        $current_lang = $this->url_rewriter->get_current_language();
        $default_lang = $this->url_rewriter->get_default_language();

        // Only show on translated pages
        if ($current_lang === $default_lang) {
            return;
        }

        // Check if edit mode is active
        $is_edit_mode = $this->is_edit_mode();

        // Build toggle URL
        $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $protocol = $is_https ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $request_uri_raw = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $current_url = $protocol . '://' . $host . $request_uri_raw;

        if ($is_edit_mode) {
            // Remove edit parameter
            $toggle_url = remove_query_arg('lg_aitrans_edit', $current_url);
            $title = '✏️ 編集モード: ON';
        } else {
            // Add edit parameter
            $toggle_url = add_query_arg('lg_aitrans_edit', '1', $current_url);
            $title = '✏️ 翻訳を編集';
        }

        $wp_admin_bar->add_node(array(
            'id'    => 'lg-aitranslator-edit',
            'title' => $title,
            'href'  => $toggle_url,
            'meta'  => array(
                'class' => 'lg-aitranslator-edit-toggle',
                'title' => $is_edit_mode ? __('Exit translation edit mode', 'lifegence-aitranslator') : __('Edit translations on this page', 'lifegence-aitranslator')
            )
        ));
    }
}

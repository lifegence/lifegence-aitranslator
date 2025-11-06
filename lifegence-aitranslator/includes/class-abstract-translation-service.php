<?php
/**
 * Abstract Translation Service Base Class
 *
 * @package LG_AITranslator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abstract base class for translation services
 * Provides common functionality for all AI translation providers
 */
abstract class LG_Abstract_Translation_Service implements LG_Translation_Service_Interface {

    /**
     * API key
     */
    protected $api_key;

    /**
     * Model name
     */
    protected $model;

    /**
     * Translation quality
     */
    protected $quality;

    /**
     * Temperature setting
     */
    protected $temperature;

    /**
     * Cache instance
     */
    protected $cache;

    /**
     * Settings
     */
    protected $settings;

    /**
     * Batch delimiter for combining texts
     */
    const BATCH_DELIMITER = "\n###TRANSLATE_SPLIT###\n";

    /**
     * Default batch size
     */
    const DEFAULT_BATCH_SIZE = 20;

    /**
     * Constructor
     *
     * @param string $provider_name Provider name for key retrieval
     * @param string $default_model Default model if not configured
     * @throws Exception If API key is not configured
     */
    protected function __construct($provider_name, $default_model) {
        $this->settings = get_option('lg_aitranslator_settings', array());
        $key_manager = new LG_API_Key_Manager();

        $this->api_key = $key_manager->get_api_key($provider_name);
        $this->model = $this->settings['model'] ?? $default_model;
        $this->quality = $this->settings['translation_quality'] ?? 'standard';
        $this->temperature = $this->settings['translation_temperature'] ?? 0.3;
        $this->cache = new LG_Translation_Cache();

        if (empty($this->api_key)) {
            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new Exception(
                /* translators: %s: Provider name (gemini or openai) */
                sprintf(__('%s API key not configured', 'lifegence-aitranslator'), ucfirst($provider_name))
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
    }

    /**
     * Get cached translation or return false
     *
     * @param string $cache_key Cache key
     * @return string|false Cached translation or false
     */
    protected function get_cached_translation($cache_key) {
        return $this->cache->get($cache_key);
    }

    /**
     * Cache translation result
     *
     * @param string $cache_key Cache key
     * @param string $translation Translation to cache
     */
    protected function set_cached_translation($cache_key, $translation) {
        $this->cache->set($cache_key, $translation);
    }

    /**
     * Generate cache key
     *
     * @param string $text Text content
     * @param string $source_lang Source language
     * @param string $target_lang Target language
     * @param string $type Content type
     * @return string Cache key
     */
    protected function generate_cache_key($text, $source_lang, $target_lang, $type = 'text') {
        $cache_version = get_option('lg_aitranslator_cache_version', 1);
        return $type . '_' . md5($text . $source_lang . $target_lang . $cache_version) . '_' . $target_lang;
    }

    /**
     * Build translation system message based on quality setting
     *
     * @param string $source_name Source language name
     * @param string $target_name Target language name
     * @return string System message
     */
    protected function build_system_message($source_name, $target_name) {
        if ($this->quality === 'high') {
            return sprintf(
                "You are a professional translator specializing in %s to %s translation. " .
                "Provide accurate, natural, and culturally appropriate translations. " .
                "Preserve all formatting including HTML tags, line breaks, and special characters. " .
                "Return only the translated text without explanations.",
                $source_name,
                $target_name
            );
        }

        return sprintf(
            "Translate from %s to %s. Preserve all formatting. Return only the translation.",
            $source_name,
            $target_name
        );
    }

    /**
     * Extract text segments from HTML
     *
     * @param string $html HTML content
     * @return array Text segments
     */
    protected function extract_text_segments($html) {
        $segments = array();
        $pattern = '/>([^<]+)</';

        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[1] as $text) {
                $text = trim($text);
                if (!empty($text) && !$this->is_untranslatable($text)) {
                    $segments[] = $text;
                }
            }
        }

        return array_unique($segments);
    }

    /**
     * Batch translate segments
     *
     * @param array $segments Text segments
     * @param string $source_lang Source language
     * @param string $target_lang Target language
     * @return array Translations
     */
    protected function batch_translate_segments($segments, $source_lang, $target_lang) {
        $batch_size = self::DEFAULT_BATCH_SIZE;
        $translations = array();

        foreach (array_chunk($segments, $batch_size) as $batch) {
            $batch_text = implode(self::BATCH_DELIMITER, $batch);

            try {
                $translated_batch = $this->translate_text($batch_text, $source_lang, $target_lang);
                $translated_segments = explode(self::BATCH_DELIMITER, $translated_batch);

                foreach ($batch as $i => $original) {
                    $translations[$original] = $translated_segments[$i] ?? $original;
                }
            } catch (Exception $e) {
                LG_Error_Handler::handle_exception($e, 'Batch segment translation failed');
                foreach ($batch as $original) {
                    $translations[$original] = $original;
                }
            }
        }

        return $translations;
    }

    /**
     * Replace segments in HTML
     *
     * @param string $html HTML content
     * @param array $segments Original segments
     * @param array $translations Translations
     * @return string Translated HTML
     */
    protected function replace_segments($html, $segments, $translations) {
        foreach ($segments as $original) {
            if (isset($translations[$original])) {
                $html = str_replace($original, $translations[$original], $html);
            }
        }
        return $html;
    }

    /**
     * Check if text is untranslatable
     *
     * @param string $text Text to check
     * @return bool
     */
    protected function is_untranslatable($text) {
        // URLs
        if (preg_match('/^https?:\/\//', $text)) {
            return true;
        }

        // Email addresses
        if (filter_var($text, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        // Numbers and punctuation only
        if (preg_match('/^[\d\s\.\,\-]+$/', $text)) {
            return true;
        }

        return false;
    }

    /**
     * Get supported languages
     *
     * @return array
     */
    public function get_supported_languages() {
        return array_keys(LG_AITranslator::get_all_languages());
    }

    /**
     * Detect language (default implementation)
     *
     * @param string $text Text to analyze
     * @return string Language code
     */
    public function detect_language($text) {
        return $this->settings['default_language'] ?? 'en';
    }

    /**
     * Abstract method: Make API call to translate text
     *
     * @param string $text Text to translate
     * @param string $source_lang Source language code
     * @param string $target_lang Target language code
     * @return string Translated text
     * @throws Exception If translation fails
     */
    abstract protected function call_translation_api($text, $source_lang, $target_lang);

    /**
     * Translate text (template method)
     *
     * @param string $text Text to translate
     * @param string $source_lang Source language code
     * @param string $target_lang Target language code
     * @return string Translated text
     * @throws Exception If translation fails
     */
    public function translate_text($text, $source_lang, $target_lang) {
        if (empty($text)) {
            return $text;
        }

        // Check cache
        $cache_key = $this->generate_cache_key($text, $source_lang, $target_lang);
        $cached = $this->get_cached_translation($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Call provider-specific API
        $translation = $this->call_translation_api($text, $source_lang, $target_lang);

        // Cache result
        $this->set_cached_translation($cache_key, $translation);

        return $translation;
    }

    /**
     * Translate HTML
     *
     * @param string $html HTML content
     * @param string $source_lang Source language code
     * @param string $target_lang Target language code
     * @return string Translated HTML
     * @throws Exception If translation fails
     */
    public function translate_html($html, $source_lang, $target_lang) {
        if (empty($html)) {
            return $html;
        }

        // Check cache
        $cache_key = $this->generate_cache_key($html, $source_lang, $target_lang, 'html');
        $cached = $this->get_cached_translation($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Extract and translate segments
        $segments = $this->extract_text_segments($html);

        if (empty($segments)) {
            return $html;
        }

        $translations = $this->batch_translate_segments($segments, $source_lang, $target_lang);
        $translated_html = $this->replace_segments($html, $segments, $translations);

        // Cache result
        $this->set_cached_translation($cache_key, $translated_html);

        return $translated_html;
    }
}

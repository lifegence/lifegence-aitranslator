<?php
/**
 * Translation Service Interface
 *
 * @package LG_AITranslator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface for translation services
 */
interface LG_Translation_Service_Interface {

    /**
     * Translate text from source to target language
     *
     * @param string $text Text to translate
     * @param string $source_lang Source language code
     * @param string $target_lang Target language code
     * @return string Translated text
     * @throws Exception If translation fails
     */
    public function translate_text($text, $source_lang, $target_lang);

    /**
     * Translate HTML content while preserving structure
     *
     * @param string $html HTML content to translate
     * @param string $source_lang Source language code
     * @param string $target_lang Target language code
     * @return string Translated HTML
     * @throws Exception If translation fails
     */
    public function translate_html($html, $source_lang, $target_lang);

    /**
     * Get supported languages
     *
     * @return array Array of supported language codes
     */
    public function get_supported_languages();

    /**
     * Detect language of given text
     *
     * @param string $text Text to analyze
     * @return string Detected language code
     */
    public function detect_language($text);

    /**
     * Validate API credentials
     *
     * @return array Array with 'valid' (bool) and 'error' (string|null) keys
     */
    public function validate_credentials();
}

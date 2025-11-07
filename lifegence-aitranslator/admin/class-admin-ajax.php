<?php
/**
 * Admin AJAX Handlers
 *
 * @package LIFEAI_AITranslator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle admin AJAX requests
 */
class LIFEAI_AITranslator_Admin_AJAX {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_lifeai_aitrans_test_gemini_key', array($this, 'test_gemini_key'));
        add_action('wp_ajax_lifeai_aitrans_test_openai_key', array($this, 'test_openai_key'));
        add_action('wp_ajax_lifeai_aitrans_clear_cache', array($this, 'clear_cache'));
        add_action('wp_ajax_lifeai_aitrans_increment_cache_version', array($this, 'increment_cache_version'));
        add_action('wp_ajax_lifeai_aitrans_update_translation', array($this, 'update_translation'));
    }

    /**
     * Test Gemini API key
     */
    public function test_gemini_key() {
        check_ajax_referer('lifeai_aitranslator_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('error' => __('Unauthorized', 'lifegence-aitranslator')));
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';

        if (empty($api_key)) {
            wp_send_json_error(array('error' => __('Please enter an API key', 'lifegence-aitranslator')));
        }

        $key_manager = new LIFEAI_API_Key_Manager();
        $result = $key_manager->validate_gemini_key($api_key);

        if ($result['valid']) {
            wp_send_json_success(array('message' => __('API key is valid!', 'lifegence-aitranslator')));
        } else {
            wp_send_json_error(array('error' => $result['error']));
        }
    }

    /**
     * Test OpenAI API key
     */
    public function test_openai_key() {
        check_ajax_referer('lifeai_aitranslator_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('error' => __('Unauthorized', 'lifegence-aitranslator')));
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';

        if (empty($api_key)) {
            wp_send_json_error(array('error' => __('Please enter an API key', 'lifegence-aitranslator')));
        }

        $key_manager = new LIFEAI_API_Key_Manager();
        $result = $key_manager->validate_openai_key($api_key);

        if ($result['valid']) {
            wp_send_json_success(array('message' => __('API key is valid!', 'lifegence-aitranslator')));
        } else {
            wp_send_json_error(array('error' => $result['error']));
        }
    }

    /**
     * Clear translation cache
     */
    public function clear_cache() {
        check_ajax_referer('lifeai_aitranslator_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('error' => __('Unauthorized', 'lifegence-aitranslator')));
        }

        $cache = new LIFEAI_Translation_Cache();
        $result = $cache->clear_all();

        if ($result) {
            wp_send_json_success(array('message' => __('Cache cleared successfully!', 'lifegence-aitranslator')));
        } else {
            wp_send_json_error(array('error' => __('Failed to clear cache', 'lifegence-aitranslator')));
        }
    }

    /**
     * Increment cache version to invalidate all existing translations
     */
    public function increment_cache_version() {
        check_ajax_referer('lifeai_aitranslator_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('error' => __('Unauthorized', 'lifegence-aitranslator')));
        }

        $current_version = get_option('lifeai_aitranslator_cache_version', 1);
        $new_version = $current_version + 1;

        $result = update_option('lifeai_aitranslator_cache_version', $new_version);

        if ($result || $current_version === $new_version) {
            wp_send_json_success(array(
                'message' => sprintf(
                    /* translators: 1: Old cache version number, 2: New cache version number */
                    __('Cache version incremented from %1$d to %2$d. All translations will be refreshed.', 'lifegence-aitranslator'),
                    $current_version,
                    $new_version
                )
            ));
        } else {
            wp_send_json_error(array('error' => __('Failed to increment cache version', 'lifegence-aitranslator')));
        }
    }

    /**
     * Update translation cache (for inline editing)
     */
    public function update_translation() {
        // Verify nonce
        if (!check_ajax_referer('lifeai_aitranslator_frontend', 'nonce', false)) {
            wp_send_json_error(array(
                'error' => __('Security check failed. Please refresh the page and try again.', 'lifegence-aitranslator')
            ), 403);
            return;
        }

        // Verify user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'error' => __('You do not have permission to edit translations.', 'lifegence-aitranslator')
            ), 403);
            return;
        }

        // Validate and sanitize inputs
        $cache_key = isset($_POST['cache_key']) ? sanitize_text_field(wp_unslash($_POST['cache_key'])) : '';
        $translation = isset($_POST['translation']) ? wp_kses_post(wp_unslash($_POST['translation'])) : '';

        if (empty($cache_key)) {
            wp_send_json_error(array('error' => __('Cache key is required', 'lifegence-aitranslator')), 400);
            return;
        }

        if (empty($translation)) {
            wp_send_json_error(array('error' => __('Translation text is required', 'lifegence-aitranslator')), 400);
            return;
        }

        // Validate cache key format (should start with 'text_' and contain hash)
        if (!preg_match('/^text_[a-f0-9]{32}_[a-z\-]+$/i', $cache_key)) {
            wp_send_json_error(array('error' => __('Invalid cache key format', 'lifegence-aitranslator')), 400);
            return;
        }

        // Update cache using WordPress transient
        $cache = new LIFEAI_Translation_Cache();
        $result = $cache->set($cache_key, $translation);

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Translation updated successfully', 'lifegence-aitranslator'),
                'cache_key' => $cache_key
            ));
        } else {
            wp_send_json_error(array('error' => __('Failed to update translation cache', 'lifegence-aitranslator')), 500);
        }
    }
}

// Initialize AJAX handlers
new LIFEAI_AITranslator_Admin_AJAX();

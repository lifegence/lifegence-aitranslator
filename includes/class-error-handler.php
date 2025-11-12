<?php
/**
 * Error Handler
 *
 * @package LIFEAI_AITranslator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Centralized error handling and logging
 */
class LIFEAI_Error_Handler {

    /**
     * Error log prefix
     */
    const LOG_PREFIX = '[LG AI Translator] ';

    /**
     * Log levels
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';

    /**
     * Log error message
     *
     * @param string $message Error message
     * @param string $level Log level
     * @param array $context Additional context
     */
    public static function log($message, $level = self::LEVEL_ERROR, $context = array()) {
        // Only log in debug mode or for errors/critical issues
        if ($level === self::LEVEL_DEBUG && (!defined('WP_DEBUG') || !WP_DEBUG)) {
            return;
        }

        $formatted_message = self::format_message($message, $level, $context);
        error_log($formatted_message); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- This is the logging implementation

        // Store critical errors for admin notice
        if (in_array($level, array(self::LEVEL_ERROR, self::LEVEL_CRITICAL))) {
            self::store_admin_notice($message, $level);
        }
    }

    /**
     * Format log message
     *
     * @param string $message Message
     * @param string $level Level
     * @param array $context Context
     * @return string Formatted message
     */
    private static function format_message($message, $level, $context) {
        $formatted = self::LOG_PREFIX . strtoupper($level) . ': ' . $message;

        if (!empty($context)) {
            $formatted .= ' | Context: ' . wp_json_encode($context);
        }

        return $formatted;
    }

    /**
     * Store error for admin notice
     *
     * @param string $message Message
     * @param string $level Level
     */
    private static function store_admin_notice($message, $level) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $notices = get_transient('lifeai_aitranslator_admin_notices') ?: array();

        $notices[] = array(
            'message' => $message,
            'level' => $level,
            'timestamp' => current_time('mysql')
        );

        // Keep only last 10 notices
        if (count($notices) > 10) {
            $notices = array_slice($notices, -10);
        }

        set_transient('lifeai_aitranslator_admin_notices', $notices, HOUR_IN_SECONDS);
    }

    /**
     * Display admin notices
     */
    public static function display_admin_notices() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $notices = get_transient('lifeai_aitranslator_admin_notices');
        if (empty($notices)) {
            return;
        }

        foreach ($notices as $notice) {
            $class = $notice['level'] === self::LEVEL_CRITICAL ? 'error' : 'warning';
            ?>
            <div class="notice notice-<?php echo esc_attr($class); ?> is-dismissible">
                <p>
                    <strong><?php echo esc_html__('Lifegence AITranslator:', 'lifegence-aitranslator'); ?></strong>
                    <?php echo esc_html($notice['message']); ?>
                    <small>(<?php echo esc_html($notice['timestamp']); ?>)</small>
                </p>
            </div>
            <?php
        }

        // Clear notices after displaying
        delete_transient('lifeai_aitranslator_admin_notices');
    }

    /**
     * Log debug message (only in WP_DEBUG mode)
     *
     * @param string $message Message
     * @param array $context Context
     */
    public static function debug($message, $context = array()) {
        self::log($message, self::LEVEL_DEBUG, $context);
    }

    /**
     * Log info message
     *
     * @param string $message Message
     * @param array $context Context
     */
    public static function info($message, $context = array()) {
        self::log($message, self::LEVEL_INFO, $context);
    }

    /**
     * Log warning message
     *
     * @param string $message Message
     * @param array $context Context
     */
    public static function warning($message, $context = array()) {
        self::log($message, self::LEVEL_WARNING, $context);
    }

    /**
     * Log error message
     *
     * @param string $message Message
     * @param array $context Context
     */
    public static function error($message, $context = array()) {
        self::log($message, self::LEVEL_ERROR, $context);
    }

    /**
     * Log critical error message
     *
     * @param string $message Message
     * @param array $context Context
     */
    public static function critical($message, $context = array()) {
        self::log($message, self::LEVEL_CRITICAL, $context);
    }

    /**
     * Handle exception
     *
     * @param Exception $exception Exception
     * @param string $context_message Context message
     */
    public static function handle_exception($exception, $context_message = '') {
        $message = $context_message ?: 'Exception occurred';
        $message .= ': ' . $exception->getMessage();

        self::error($message, array(
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ));
    }
}

<?php
/**
 * Language Switcher Widget
 *
 * @package LIFEAI_AITranslator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Language switcher widget class
 */
class LIFEAI_Language_Switcher_Widget extends WP_Widget {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'lifeai_language_switcher',
            __('Lifegence Language Switcher', 'lifegence-aitranslator'),
            array(
                'description' => __('Display language switcher for AI translation', 'lifegence-aitranslator'),
                'classname' => 'lg-language-switcher-widget'
            )
        );
    }

    /**
     * Front-end widget output
     */
    public function widget($args, $instance) {
        $settings = get_option('lifeai_aitranslator_settings', array());

        if (empty($settings['enabled'])) {
            return;
        }

        $title = !empty($instance['title']) ? $instance['title'] : '';
        $show_flags = !empty($instance['show_flags']);
        $show_native = !empty($instance['show_native']);

        echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

        if (!empty($title)) {
            echo $args['before_title'] . esc_html($title) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        $type = isset($instance['type']) ? sanitize_text_field($instance['type']) : 'dropdown';
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->render_switcher(array(
            'type' => $type,
            'flags' => $show_flags ? 'yes' : 'no',
            'native_names' => $show_native ? 'yes' : 'no'
        ));
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

        echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Widget settings form
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Select Language', 'lifegence-aitranslator');
        $type = !empty($instance['type']) ? $instance['type'] : 'dropdown';
        $show_flags = isset($instance['show_flags']) ? (bool) $instance['show_flags'] : true;
        $show_native = isset($instance['show_native']) ? (bool) $instance['show_native'] : true;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'lifegence-aitranslator'); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                value="<?php echo esc_attr($title); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('type')); ?>">
                <?php esc_html_e('Display Type:', 'lifegence-aitranslator'); ?>
            </label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('type')); ?>"
                name="<?php echo esc_attr($this->get_field_name('type')); ?>">
                <option value="dropdown" <?php selected($type, 'dropdown'); ?>><?php esc_html_e('Dropdown', 'lifegence-aitranslator'); ?></option>
                <option value="list" <?php selected($type, 'list'); ?>><?php esc_html_e('List', 'lifegence-aitranslator'); ?></option>
                <option value="flags" <?php selected($type, 'flags'); ?>><?php esc_html_e('Flags Only', 'lifegence-aitranslator'); ?></option>
            </select>
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_flags); ?>
                id="<?php echo esc_attr($this->get_field_id('show_flags')); ?>"
                name="<?php echo esc_attr($this->get_field_name('show_flags')); ?>" value="1">
            <label for="<?php echo esc_attr($this->get_field_id('show_flags')); ?>">
                <?php esc_html_e('Show flags', 'lifegence-aitranslator'); ?>
            </label>
        </p>

        <p>
            <input class="checkbox" type="checkbox" <?php checked($show_native); ?>
                id="<?php echo esc_attr($this->get_field_id('show_native')); ?>"
                name="<?php echo esc_attr($this->get_field_name('show_native')); ?>" value="1">
            <label for="<?php echo esc_attr($this->get_field_id('show_native')); ?>">
                <?php esc_html_e('Show native names', 'lifegence-aitranslator'); ?>
            </label>
        </p>
        <?php
    }

    /**
     * Update widget settings
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['type'] = !empty($new_instance['type']) ? sanitize_text_field($new_instance['type']) : 'dropdown';
        $instance['show_flags'] = !empty($new_instance['show_flags']);
        $instance['show_native'] = !empty($new_instance['show_native']);
        return $instance;
    }

    /**
     * Render language switcher
     */
    public function render_switcher($atts) {
        $settings = get_option('lifeai_aitranslator_settings', array());
        $supported_langs = $settings['supported_languages'] ?? array();
        $default_lang = $settings['default_language'] ?? 'en';
        $current_lang = $this->get_current_language();

        $type = $atts['type'] ?? 'dropdown';
        $show_flags = ($atts['flags'] ?? 'yes') === 'yes';
        $show_native = ($atts['native_names'] ?? 'yes') === 'yes';

        ob_start();

        if ($type === 'dropdown') {
            $this->render_dropdown($supported_langs, $current_lang, $show_flags, $show_native);
        } elseif ($type === 'list') {
            $this->render_list($supported_langs, $current_lang, $show_flags, $show_native);
        } elseif ($type === 'flags') {
            $this->render_flags($supported_langs, $current_lang);
        }

        return ob_get_clean();
    }

    /**
     * Render dropdown switcher
     */
    private function render_dropdown($langs, $current, $show_flags, $show_native) {
        $all_languages = LIFEAI_AITranslator::get_all_languages();
        ?>
        <div class="lg-lang-switcher lg-lang-dropdown">
            <select id="lg-lang-select" class="lg-lang-select">
                <?php foreach ($langs as $code): ?>
                    <?php if (isset($all_languages[$code])): ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php selected($current, $code); ?>>
                            <?php if ($show_flags): ?>
                                <?php echo esc_html($this->get_flag_emoji($code)); ?>
                            <?php endif; ?>
                            <?php echo esc_html($all_languages[$code]); ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    /**
     * Render list switcher
     */
    private function render_list($langs, $current, $show_flags, $show_native) {
        $all_languages = LIFEAI_AITranslator::get_all_languages();
        ?>
        <div class="lg-lang-switcher lg-lang-list">
            <ul class="lg-lang-list">
                <?php foreach ($langs as $code): ?>
                    <?php if (isset($all_languages[$code])): ?>
                        <li class="<?php echo $current === $code ? 'active' : ''; ?>">
                            <a href="<?php echo esc_url($this->get_language_url($code)); ?>" data-lang="<?php echo esc_attr($code); ?>" class="lg-lang-link">
                                <?php if ($show_flags): ?>
                                    <span class="lg-lang-flag"><?php echo esc_html($this->get_flag_emoji($code)); ?></span>
                                <?php endif; ?>
                                <span class="lg-lang-name"><?php echo esc_html($all_languages[$code]); ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }

    /**
     * Render flags only
     */
    private function render_flags($langs, $current) {
        $all_languages = LIFEAI_AITranslator::get_all_languages();
        ?>
        <div class="lg-lang-switcher lg-lang-flags">
            <?php foreach ($langs as $code): ?>
                <?php if (isset($all_languages[$code])): ?>
                    <a href="<?php echo esc_url($this->get_language_url($code)); ?>" data-lang="<?php echo esc_attr($code); ?>"
                        class="lg-lang-flag-link <?php echo $current === $code ? 'active' : ''; ?>"
                        title="<?php echo esc_attr($all_languages[$code]); ?>">
                        <?php echo esc_html($this->get_flag_emoji($code)); ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Get flag emoji for language code
     */
    private function get_flag_emoji($code) {
        $flags = array(
            'en' => '🇬🇧',
            'ja' => '🇯🇵',
            'zh-CN' => '🇨🇳',
            'zh-TW' => '🇹🇼',
            'ko' => '🇰🇷',
            'es' => '🇪🇸',
            'fr' => '🇫🇷',
            'de' => '🇩🇪',
            'it' => '🇮🇹',
            'pt' => '🇵🇹',
            'ru' => '🇷🇺',
            'ar' => '🇸🇦',
            'hi' => '🇮🇳',
            'th' => '🇹🇭',
            'vi' => '🇻🇳',
            'id' => '🇮🇩',
            'tr' => '🇹🇷',
            'pl' => '🇵🇱',
            'nl' => '🇳🇱',
            'sv' => '🇸🇪'
        );

        return $flags[$code] ?? '🌐';
    }

    /**
     * Get current language
     */
    private function get_current_language() {
        // Use URL rewriter to detect language from URL
        if (class_exists('LIFEAI_URL_Rewriter')) {
            $url_rewriter = new LIFEAI_URL_Rewriter();
            return $url_rewriter->get_current_language();
        }

        // Fallback to old method
        $settings = get_option('lifeai_aitranslator_settings', array());
        $default = $settings['default_language'] ?? 'en';

        if (isset($_COOKIE['lifeai_aitranslator_lang'])) {
            return sanitize_text_field(wp_unslash($_COOKIE['lifeai_aitranslator_lang']));
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Public query parameter for language selection
        if (isset($_GET['lang'])) {
            return sanitize_text_field(wp_unslash($_GET['lang']));
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        return $default;
    }

    /**
     * Get language-specific URL
     */
    private function get_language_url($lang) {
        if (class_exists('LIFEAI_URL_Rewriter')) {
            $url_rewriter = new LIFEAI_URL_Rewriter();
            return $url_rewriter->get_language_url($lang);
        }

        // Fallback: use query parameter
        $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $protocol = $is_https ? 'https' : 'http';
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        $current_url = $protocol . '://' . $host . $request_uri;
        return add_query_arg('lang', $lang, $current_url);
    }

    /**
     * Shortcode handler for language switcher
     */
    public static function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'type' => 'dropdown',
            'flags' => 'yes',
            'native_names' => 'yes'
        ), $atts, 'lifeai_language_switcher');

        $widget = new self();
        return $widget->render_switcher($atts);
    }
}

/**
 * Register language switcher shortcode
 */
add_shortcode('lifeai_language_switcher', array('LIFEAI_Language_Switcher_Widget', 'shortcode_handler'));

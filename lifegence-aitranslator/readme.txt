=== Lifegence AITranslator ===
Contributors: lifegence
Tags: translation, multilingual, AI, gemini, openai
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered automatic translation plugin using Google Gemini and OpenAI GPT. Translate your entire WordPress site into multiple languages instantly.

== Description ==

**Lifegence AITranslator** transforms your WordPress site into a multilingual platform using cutting-edge AI technology from Google Gemini and OpenAI GPT.

= Key Features =

**Advanced AI Translation**

* Dual AI Engine Support: Choose between Google Gemini API or OpenAI GPT API
* High-Quality Translations: Natural, context-aware translations that sound human-written
* Intelligent Text Processing: Automatically skips URLs, email addresses, and code blocks
* Batch Translation: Optimized API usage with single-request-per-page processing

**Multilingual Support**

Support for 20+ preset languages including:

* English, Japanese (日本語), Korean (한국어)
* Chinese Simplified (简体中文), Chinese Traditional (繁體中文)
* Spanish, French, German, Italian, Portuguese
* Russian, Arabic, Hindi, Thai, Vietnamese, Indonesian
* Turkish, Polish, Dutch, Swedish

**Plus unlimited custom languages:**

* Add any language not in the preset list via admin interface
* Popular additions: Tagalog, Filipino, Malay, Bengali, Urdu, Persian, Hebrew, Greek, and more
* Simple interface: just enter language code and name, click Add

**Inline Translation Editor**

* Visual Edit Mode: Click to edit any translated text directly on your page
* Real-time Updates: Changes are immediately visible after saving
* Admin Bar Integration: Toggle edit mode with one click from the admin bar
* Cache Override: Manually correct AI translations when needed

**Performance Optimized**

* Smart Caching System: WordPress Transient-based caching for lightning-fast page loads
* Cache Version Control: Invalidate all translations with one click to force re-translation
* Selective Caching: Individual text caching for efficient memory usage
* Rate Limit Management: Automatic API request optimization to avoid rate limits

**SEO-Friendly**

* Language Prefix URLs: Clean URL structure like `/ko/about`, `/ja/contact`
* Hreflang Tags: Automatic hreflang tag generation for proper SEO
* Language Attributes: Dynamic HTML lang attribute updates
* Search Engine Friendly: Properly indexed multilingual content

**Flexible Language Switcher**

Multiple display formats:

* Dropdown selector
* Flag icons
* Text links
* Custom positioning via shortcode or widget

= Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* Either Google Gemini API key OR OpenAI API key
* Modern web browser with JavaScript enabled

= API Services =

This plugin requires external API services:

* **Google Gemini API**: Free tier includes 15 requests per minute
* **OpenAI API**: Pay-per-use pricing (~$0.15 per 1M tokens)

Please review the respective API pricing before use as API usage may incur costs depending on your usage volume.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/lifegence-aitranslator/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to Settings → Lifegence AITranslator
4. Enter your Google Gemini API key or OpenAI API key
5. Select your default language and supported languages
6. Click "Save Settings"
7. Add language switcher to your site using widget or shortcode

== Frequently Asked Questions ==

= Do I need both Gemini and OpenAI API keys? =

No, you only need one. Choose either Google Gemini API or OpenAI API based on your preference and budget.

= How much does it cost to use this plugin? =

The plugin itself is free, but you'll need an API key from either Google Gemini or OpenAI. Google Gemini offers a free tier with 15 requests per minute. OpenAI charges per token usage (~$0.15 per 1M tokens with GPT-4o-mini).

= Can I add languages not in the preset list? =

Yes! Go to Settings → Lifegence AITranslator → General tab, scroll to the "Custom Languages" section. Enter the language code (e.g., `tl` for Tagalog) and name, click "Add Custom Language", then save settings. Custom languages will appear in all language selectors.

= Can I edit translations manually? =

Yes! Simply click "✏️ Edit Translation" in the admin bar (or add ?edit_translation=1 to the URL), then click any text on the page to edit it. Type your correction, save, and refresh. Perfect for fixing brand names, technical terms, or adjusting tone.

= How does caching work? =

The plugin caches all translations using WordPress Transients. This means once a page is translated, subsequent visits load instantly from cache without making API calls. Cache duration is configurable in settings (default: 7 days).

= What happens if I hit API rate limits? =

The plugin optimizes API usage by translating entire pages in a single request and caching results. If you hit rate limits, you can:
* Enable caching to minimize API calls
* Wait a few minutes before retrying
* Upgrade to a paid API tier

= Can I use this with other multilingual plugins? =

This plugin is designed to work standalone. Using it with other translation plugins like WPML or Polylang may cause conflicts.

= Is this plugin SEO-friendly? =

Yes! The plugin generates proper hreflang tags, uses clean language-prefix URLs, and updates HTML lang attributes for proper search engine indexing.

= How do I add a language switcher to my site? =

Use one of these methods:
* Widget: Go to Appearance → Widgets and add "LG Language Switcher"
* Shortcode: Add `[lg_lang_switcher type="dropdown"]` to any page or post
* PHP: Add `<?php echo do_shortcode('[lg_lang_switcher]'); ?>` to your theme

= Can I force re-translation of all content? =

Yes! In Settings → Lifegence AITranslator → Cache tab, click "Increment Cache Version (Force Re-translate)" to invalidate all existing translations and force fresh translation on next page load. This is useful when you want to refresh all translations after improving translation prompts or when translations need systematic updates.

= What content gets translated? =

The plugin translates:
* Page and post titles
* Page and post content
* Excerpts
* Widget titles and text
* Menu items
* Category and tag names
* Custom post types

= What content does NOT get translated? =

The plugin automatically skips:
* URLs
* Email addresses
* JavaScript code
* CSS styles
* Numbers and symbols only
* Very short text (< 2 characters)

== Screenshots ==

1. Admin settings page - Configure API keys and languages
2. Language switcher dropdown on frontend
3. Language switcher with flag icons
4. Inline translation editor mode
5. Edit any translation directly on the page
6. Admin bar integration for quick edit mode toggle
7. Cache management settings
8. API key validation with test connection

== Changelog ==

= 1.0.0 =
* Initial release
* Dual AI engine support (Google Gemini + OpenAI GPT)
* Support for 20+ preset languages
* Custom language support - add unlimited languages via admin interface
* Inline translation editor with visual edit mode
* Smart caching system with WordPress Transients
* Cache version management for forced re-translation
* SEO-friendly URLs with language prefixes
* Automatic hreflang tag generation
* Multiple language switcher formats (dropdown, flags, list)
* Admin bar integration for edit mode
* Real-time translation updates via AJAX
* API key validation with test connection
* Rate limit optimization with batch translation
* Intelligent text filtering (skip URLs, emails, code)
* Dynamic language management with JavaScript UI
* Language code validation and duplicate prevention
* Full test coverage (38 automated tests)

== Upgrade Notice ==

= 1.0.0 =
Initial release of Lifegence AITranslator. Translate your entire WordPress site with AI!

== Developer Hooks ==

= Filters =

Modify translation before caching:
`
add_filter('lg_aitranslator_translation', function($translation, $original, $lang) {
    return $translation;
}, 10, 3);
`

Skip translation for specific content:
`
add_filter('lg_aitranslator_should_translate', function($should_translate, $content) {
    return $should_translate;
}, 10, 2);
`

Add custom language:
`
add_filter('lg_aitranslator_languages', function($languages) {
    $languages['custom'] = 'Custom Language';
    return $languages;
});
`

= Shortcodes =

Language switcher dropdown:
`[lg_lang_switcher type="dropdown"]`

Language switcher with flags:
`[lg_lang_switcher type="flags"]`

Language switcher as list:
`[lg_lang_switcher type="list"]`

== Privacy Policy ==

This plugin sends content to external AI services (Google Gemini or OpenAI) for translation. Please ensure you have appropriate permissions to send user-generated content to these services and review their respective privacy policies:

* Google Gemini Privacy Policy: https://policies.google.com/privacy
* OpenAI Privacy Policy: https://openai.com/privacy/

The plugin does not store any personal data beyond what WordPress normally stores. API keys are stored in the WordPress database using standard WordPress options.

== Support ==

For support, bug reports, or feature requests, please visit:
https://github.com/lifegence/lifegence-aitranslator

== Credits ==

Developed with ❤️ using:
* Google Gemini API
* OpenAI GPT API
* WordPress Core Functions

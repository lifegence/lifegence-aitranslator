jQuery(document).ready(function($) {
    // Handle language switcher dropdown
    $('#lg-lang-select').on('change', function() {
        var lang = $(this).val();
        changeLanguage(lang);
    });

    // Handle language switcher links - now uses href directly
    // Just set cookie for preference storage
    $(document).on('click', '.lg-lang-link, .lg-lang-flag-link', function(e) {
        var lang = $(this).data('lang');
        // Set cookie for language preference
        document.cookie = 'lg_aitranslator_lang=' + lang + '; path=/; max-age=31536000';
        // Let the href handle navigation (already set to correct URL)
    });

    function changeLanguage(lang) {
        // Set cookie for preference
        document.cookie = 'lg_aitranslator_lang=' + lang + '; path=/; max-age=31536000';

        // Build new URL with language prefix
        var currentPath = window.location.pathname;
        var newPath;

        // Remove existing language prefix
        var langPattern = /^\/(en|ja|zh-CN|zh-TW|ko|es|fr|de|it|pt|ru|ar|hi|th|vi|id|tr|pl|nl|sv)\//i;
        currentPath = currentPath.replace(langPattern, '/');

        // Add new language prefix (skip for default language)
        var defaultLang = lgAITranslatorFrontend.defaultLang;
        if (lang !== defaultLang) {
            newPath = '/' + lang + currentPath;
        } else {
            newPath = currentPath;
        }

        // Navigate to new URL
        window.location.href = newPath + window.location.search + window.location.hash;
    }

    // Get current language from URL path
    function getCurrentLanguage() {
        var path = window.location.pathname;
        var langPattern = /^\/(en|ja|zh-CN|zh-TW|ko|es|fr|de|it|pt|ru|ar|hi|th|vi|id|tr|pl|nl|sv)\//i;
        var match = path.match(langPattern);

        if (match && match[1]) {
            return match[1];
        }

        return lgAITranslatorFrontend.defaultLang;
    }

    // Translation Edit Mode
    if (window.location.search.includes('lg_aitrans_edit=1')) {
        // Edit button click handler
        $(document).on('click', '.lg-aitrans-edit-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var $btn = $(this);
            var $wrapper = $btn.closest('.lg-aitrans-editable');
            var original = $wrapper.data('original');
            var cacheKey = $wrapper.data('cache-key');
            var lang = $wrapper.data('lang');
            var currentText = $wrapper.contents().filter(function() {
                return this.nodeType === 3; // Text nodes only
            }).text().trim();

            // If no text nodes, get all text except button
            if (!currentText) {
                currentText = $wrapper.clone().children('.lg-aitrans-edit-btn').remove().end().text().trim();
            }

            // Create inline editor
            var $editor = $('<div class="lg-aitrans-inline-editor">' +
                '<textarea class="lg-aitrans-textarea">' + currentText + '</textarea>' +
                '<div class="lg-aitrans-editor-actions">' +
                    '<button class="lg-aitrans-save-btn">💾 保存</button>' +
                    '<button class="lg-aitrans-cancel-btn">❌ キャンセル</button>' +
                '</div>' +
                '<div class="lg-aitrans-editor-info">元のテキスト: ' + original + '</div>' +
            '</div>');

            // Replace wrapper content with editor
            var originalHtml = $wrapper.html();
            $wrapper.html($editor);

            // Cancel button
            $editor.find('.lg-aitrans-cancel-btn').on('click', function(e) {
                e.preventDefault();
                $wrapper.html(originalHtml);
            });

            // Save button
            $editor.find('.lg-aitrans-save-btn').on('click', function(e) {
                e.preventDefault();

                var $saveBtn = $(this);
                var newTranslation = $editor.find('.lg-aitrans-textarea').val();

                $saveBtn.prop('disabled', true).text('💾 保存中...');

                $.post(lgAITranslatorFrontend.ajaxurl, {
                    action: 'lg_aitrans_update_translation',
                    cache_key: cacheKey,
                    translation: newTranslation,
                    nonce: lgAITranslatorFrontend.nonce
                }, function(response) {
                    if (response.success) {
                        // Update display
                        $wrapper.html(newTranslation + '<button class="lg-aitrans-edit-btn" data-index="0">✏️</button>');

                        // Show success message
                        var $success = $('<span class="lg-aitrans-success">✓ 保存しました</span>');
                        $wrapper.append($success);
                        setTimeout(function() {
                            $success.fadeOut(function() { $(this).remove(); });
                        }, 2000);
                    } else {
                        alert('保存失敗: ' + (response.data.error || '不明なエラー'));
                        $wrapper.html(originalHtml);
                    }
                }).fail(function() {
                    alert('通信エラーが発生しました');
                    $wrapper.html(originalHtml);
                });
            });

            // Auto-resize textarea
            var $textarea = $editor.find('.lg-aitrans-textarea');
            $textarea.css('height', 'auto').css('height', $textarea[0].scrollHeight + 'px');
        });
    }
});

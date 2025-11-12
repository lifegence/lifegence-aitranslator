# Translation Cache Override Guide

## Overview

The Lifegence AI Translator plugin uses caching to improve performance and reduce API costs. However, there are situations where you need to force re-translation of content. This guide explains the cache override features available.

## Cache Override Methods

### 1. Cache Version Increment (Recommended)

The cache version increment method is the **recommended approach** for most scenarios. It invalidates all existing translations by incrementing a version number, forcing the system to regenerate translations on next access.

**Advantages:**
- Does not physically delete cached data immediately
- Lower database load
- Cached data remains available during transition
- Safer for high-traffic sites

**How it works:**
The plugin uses a version number (`lifeai_aitranslator_cache_version`) that is included in all cache keys. When you increment the version, all existing cache keys become invalid because they reference an old version number.

**Access via:**
- WordPress Admin → Settings → Lifegence AI Translator → Cache Settings
- Click "Increment Cache Version" button
- AJAX endpoint: `wp_ajax_lifeai_aitrans_increment_cache_version`

**Code location:** `admin/class-admin-ajax.php:104-128`

### 2. Clear Cache (Complete Deletion)

The clear cache method **physically deletes** all translation cache data from the database.

**Advantages:**
- Immediately frees database space
- Completely removes old translation data
- Useful for debugging or testing

**Disadvantages:**
- Can cause temporary performance impact on high-traffic sites
- All translations must be regenerated from scratch
- Higher database load during deletion

**Access via:**
- WordPress Admin → Settings → Lifegence AI Translator → Cache Settings
- Click "Clear Cache" button
- AJAX endpoint: `wp_ajax_lifeai_aitrans_clear_cache`

**Code location:** `admin/class-admin-ajax.php:84-99`

## When to Use Cache Override

### Common Use Cases

1. **Content Updated**: Original content has been modified and needs re-translation
2. **Translation Quality**: AI-generated translations need improvement or regeneration
3. **Configuration Changes**: Changed translation service (Gemini ↔ OpenAI) or settings
4. **Language Updates**: Modified supported languages or default language
5. **Testing**: Verifying translation behavior or debugging issues
6. **Migration**: Moving from another translation system

### Recommended Approach

| Scenario | Recommended Method | Reason |
|----------|-------------------|---------|
| Regular content updates | Cache Version Increment | Safer, gradual cache refresh |
| Changed translation service | Cache Version Increment | Allows both old/new to coexist temporarily |
| Modified language settings | Cache Version Increment | Prevents disruption |
| Database cleanup needed | Clear Cache | Physically removes old data |
| Debugging translation issues | Clear Cache | Ensures completely fresh start |
| High-traffic production site | Cache Version Increment | Lower performance impact |

## Technical Implementation

### Cache Version System

The cache versioning system works by including a version number in every cache key:

```php
// From class-content-translator.php:753-755
private function generate_cache_key($type, $id, $lang, $content) {
    $cache_version = get_option('lifeai_aitranslator_cache_version', 1);
    $hash = md5($content . $cache_version);
    return "{$type}_{$hash}_{$lang}";
}
```

When the version is incremented:
1. Current version retrieved: `get_option('lifeai_aitranslator_cache_version', 1)`
2. Version incremented: `$new_version = $current_version + 1`
3. New version saved: `update_option('lifeai_aitranslator_cache_version', $new_version)`
4. All cache lookups now use new version in key generation
5. Old cached data becomes unreachable (but still exists in database)

### Cache Backends Supported

The plugin supports multiple cache backends:

1. **WordPress Transients** (default)
   - Stores cache in WordPress options table
   - No additional setup required
   - Suitable for most sites

2. **Redis**
   - High-performance in-memory cache
   - Requires Redis server and PHP Redis extension
   - Best for high-traffic sites

3. **Memcached**
   - Distributed memory caching
   - Requires Memcached server setup
   - Good for multi-server environments

**Code location:** `includes/class-translation-cache.php`

### Cache Key Format

Cache keys follow this pattern:

- **Page cache**: `page_{md5_hash}_{language_code}`
- **Text fragment**: `text_{md5_hash}_{language_code}`
- **Post title**: `post_title_{md5_hash}_{language_code}`
- **Post content**: `post_content_{md5_hash}_{language_code}`
- **Widget**: `widget_title_{md5_hash}_{language_code}`

Example: `lifeai_aitrans_text_5d41402abc4b2a76b9719d911017c592_ja`

## Cache Statistics

Monitor cache usage via the translation cache class:

```php
$cache = new LIFEAI_Translation_Cache();
$stats = $cache->get_stats();
```

Returns:
- `backend`: Current cache backend (transients/redis/memcached)
- `enabled`: Whether caching is enabled
- `ttl`: Time-to-live in seconds (default: 86400 = 24 hours)
- `total_keys`: Number of cached items
- `total_size`: Total cache size in bytes

**Code location:** `includes/class-translation-cache.php:162-191`

## Best Practices

### Performance Optimization

1. **Use Cache Version Increment** for routine updates on production sites
2. **Schedule cache clears** during low-traffic periods if needed
3. **Monitor cache size** to prevent database bloat
4. **Consider Redis/Memcached** for high-traffic sites

### Development Workflow

1. **Development**: Use Clear Cache freely for testing
2. **Staging**: Test Cache Version Increment before production
3. **Production**: Prefer Cache Version Increment, monitor performance

### Troubleshooting

**Problem: Translations not updating after content changes**
- **Solution**: Use Cache Version Increment to force re-translation

**Problem: Database growing too large**
- **Solution**: Use Clear Cache to physically remove old data

**Problem: Mixed old/new translations appearing**
- **Solution**: Clear Cache completely, then reload pages

**Problem: Slow performance after cache clear**
- **Solution**: Normal - translations regenerate on first access. Consider pre-warming cache for critical pages.

## Security Considerations

Both cache override methods require `manage_options` capability (WordPress administrator).

**Nonce verification** is enforced:
```php
check_ajax_referer('lifeai_aitranslator_admin', 'nonce');
```

**Permission check**:
```php
if (!current_user_can('manage_options')) {
    wp_send_json_error(array('error' => __('Unauthorized', 'lifegence-aitranslator')));
}
```

## API Reference

### AJAX Endpoints

#### Increment Cache Version
```javascript
jQuery.ajax({
    url: ajaxurl,
    method: 'POST',
    data: {
        action: 'lifeai_aitrans_increment_cache_version',
        nonce: lifeai_aitranslator_admin.nonce
    },
    success: function(response) {
        console.log(response.data.message);
    }
});
```

#### Clear Cache
```javascript
jQuery.ajax({
    url: ajaxurl,
    method: 'POST',
    data: {
        action: 'lifeai_aitrans_clear_cache',
        nonce: lifeai_aitranslator_admin.nonce
    },
    success: function(response) {
        console.log(response.data.message);
    }
});
```

### PHP Methods

#### Increment Version Programmatically
```php
$current_version = get_option('lifeai_aitranslator_cache_version', 1);
update_option('lifeai_aitranslator_cache_version', $current_version + 1);
```

#### Clear Cache Programmatically
```php
$cache = new LIFEAI_Translation_Cache();
$cache->clear_all();
```

#### Clear Specific Item
```php
$cache = new LIFEAI_Translation_Cache();
$cache->delete('text_5d41402abc4b2a76b9719d911017c592_ja');
```

## Related Documentation

- [Admin Settings Configuration](../developer/admin-settings-configuration.md)
- [Translation Cache Class](../developer/README.md#translation-cache)
- [WordPress Transients API](https://developer.wordpress.org/apis/handbook/transients/)

## Changelog

### Version 1.0.0
- Initial cache override documentation
- Cache version increment system
- Clear cache functionality
- Multi-backend support (Transients/Redis/Memcached)

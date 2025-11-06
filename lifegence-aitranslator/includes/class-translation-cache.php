<?php
/**
 * Translation Cache Manager
 *
 * @package LG_AITranslator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages translation caching using WordPress transients
 */
class LG_Translation_Cache {

    /**
     * Cache prefix
     */
    private $prefix = 'lg_aitrans_';

    /**
     * Cache backend type
     */
    private $backend;

    /**
     * Cache TTL
     */
    private $ttl;

    /**
     * Enabled flag
     */
    private $enabled;

    /**
     * Constructor
     */
    public function __construct() {
        $settings = get_option('lg_aitranslator_settings', array());

        $this->enabled = $settings['cache_enabled'] ?? true;
        $this->ttl = $settings['cache_ttl'] ?? 86400; // 24 hours default
        $this->backend = $settings['cache_backend'] ?? 'transients';
    }

    /**
     * Get cached value
     *
     * @param string $key Cache key
     * @return mixed|false Cached value or false if not found
     */
    public function get($key) {
        if (!$this->enabled) {
            return false;
        }

        $full_key = $this->prefix . $key;

        switch ($this->backend) {
            case 'redis':
                return $this->get_from_redis($full_key);

            case 'memcached':
                return $this->get_from_memcached($full_key);

            default:
                return get_transient($full_key);
        }
    }

    /**
     * Set cached value
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int|null $ttl Optional TTL override
     * @return bool Success
     */
    public function set($key, $value, $ttl = null) {
        if (!$this->enabled) {
            return false;
        }

        $full_key = $this->prefix . $key;
        $ttl = $ttl ?? $this->ttl;

        switch ($this->backend) {
            case 'redis':
                return $this->set_to_redis($full_key, $value, $ttl);

            case 'memcached':
                return $this->set_to_memcached($full_key, $value, $ttl);

            default:
                return set_transient($full_key, $value, $ttl);
        }
    }

    /**
     * Delete cached value
     *
     * @param string $key Cache key
     * @return bool Success
     */
    public function delete($key) {
        $full_key = $this->prefix . $key;

        switch ($this->backend) {
            case 'redis':
                return $this->delete_from_redis($full_key);

            case 'memcached':
                return $this->delete_from_memcached($full_key);

            default:
                return delete_transient($full_key);
        }
    }

    /**
     * Clear all translation cache
     *
     * @return bool Success
     */
    public function clear_all() {
        global $wpdb;

        switch ($this->backend) {
            case 'redis':
                return $this->flush_redis_pattern($this->prefix . '*');

            case 'memcached':
                return $this->flush_memcached();

            default:
                // Clear WordPress transients
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for cache cleanup
                $result = $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
                        '_transient_' . $this->prefix . '%',
                        '_transient_timeout_' . $this->prefix . '%'
                    )
                );

                // Increment cache version to invalidate all keys
                $current_version = get_option('lg_aitranslator_cache_version', 1);
                update_option('lg_aitranslator_cache_version', $current_version + 1);

                return $result !== false;
        }
    }

    /**
     * Get cache statistics
     *
     * @return array Statistics
     */
    public function get_stats() {
        global $wpdb;

        $stats = array(
            'backend' => $this->backend,
            'enabled' => $this->enabled,
            'ttl' => $this->ttl,
            'total_keys' => 0,
            'total_size' => 0
        );

        if ($this->backend === 'transients') {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Required for cache stats
            $result = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT COUNT(*) as count, SUM(LENGTH(option_value)) as size
                     FROM {$wpdb->options}
                     WHERE option_name LIKE %s",
                    '_transient_' . $this->prefix . '%'
                )
            );

            if (!empty($result[0])) {
                $stats['total_keys'] = intval($result[0]->count);
                $stats['total_size'] = intval($result[0]->size);
            }
        }

        return $stats;
    }

    /**
     * Redis getter
     *
     * @param string $key Cache key
     * @return mixed|false
     */
    private function get_from_redis($key) {
        if (!class_exists('Redis')) {
            return false;
        }

        try {
            $redis = $this->get_redis_connection();
            if (!$redis) {
                return false;
            }

            $value = $redis->get($key);
            return $value !== false ? maybe_unserialize($value) : false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Redis setter
     *
     * @param string $key Cache key
     * @param mixed $value Value
     * @param int $ttl TTL in seconds
     * @return bool
     */
    private function set_to_redis($key, $value, $ttl) {
        if (!class_exists('Redis')) {
            return false;
        }

        try {
            $redis = $this->get_redis_connection();
            if (!$redis) {
                return false;
            }

            return $redis->setex($key, $ttl, maybe_serialize($value));
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Redis deleter
     *
     * @param string $key Cache key
     * @return bool
     */
    private function delete_from_redis($key) {
        if (!class_exists('Redis')) {
            return false;
        }

        try {
            $redis = $this->get_redis_connection();
            if (!$redis) {
                return false;
            }

            return $redis->del($key) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Flush Redis by pattern
     *
     * @param string $pattern Key pattern
     * @return bool
     */
    private function flush_redis_pattern($pattern) {
        if (!class_exists('Redis')) {
            return false;
        }

        try {
            $redis = $this->get_redis_connection();
            if (!$redis) {
                return false;
            }

            $keys = $redis->keys($pattern);
            if (!empty($keys)) {
                $redis->del($keys);
            }

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get Redis connection
     *
     * @return Redis|false
     */
    private function get_redis_connection() {
        static $redis = null;

        if ($redis !== null) {
            return $redis;
        }

        try {
            $settings = get_option('lg_aitranslator_settings', array());

            $redis = new Redis();
            $connected = $redis->connect(
                $settings['redis_host'] ?? '127.0.0.1',
                $settings['redis_port'] ?? 6379,
                2 // timeout
            );

            if (!$connected) {
                return false;
            }

            if (!empty($settings['redis_password'])) {
                $redis->auth($settings['redis_password']);
            }

            if (!empty($settings['redis_database'])) {
                $redis->select($settings['redis_database']);
            }

            return $redis;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Memcached getter (stub implementation)
     */
    private function get_from_memcached($key) {
        // Implement if needed
        return false;
    }

    /**
     * Memcached setter (stub implementation)
     */
    private function set_to_memcached($key, $value, $ttl) {
        // Implement if needed
        return false;
    }

    /**
     * Memcached deleter (stub implementation)
     */
    private function delete_from_memcached($key) {
        // Implement if needed
        return false;
    }

    /**
     * Flush Memcached (stub implementation)
     */
    private function flush_memcached() {
        // Implement if needed
        return false;
    }
}

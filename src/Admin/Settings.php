<?php
/**
 * Settings Management
 *
 * @package AB\BricksAutoToken\Admin
 */

namespace AB\BricksAutoToken\Admin;

defined('ABSPATH') || exit;

/**
 * Handles plugin settings
 */
final class Settings {
    /**
     * Option name for cache TTL
     */
    private const CACHE_TTL_OPTION = 'ab_bricks_auto_token_cache_ttl';

    /**
     * Cache TTL presets
     */
    public const TTL_DISABLED = 0;
    public const TTL_SHORT = 5;
    public const TTL_MEDIUM = 20;
    public const TTL_LONG = 60;
    public const TTL_DAY = 86400;

    /**
     * Get cache TTL in seconds
     *
     * @return int TTL in seconds, 0 means disabled
     */
    public static function get_cache_ttl(): int {
        $ttl = get_option(self::CACHE_TTL_OPTION, self::TTL_MEDIUM);
        return max(0, (int) $ttl);
    }

    /**
     * Update cache TTL
     *
     * @param int $ttl TTL in seconds.
     * @return bool
     */
    public static function update_cache_ttl(int $ttl): bool {
        return update_option(self::CACHE_TTL_OPTION, max(0, $ttl));
    }

    /**
     * Check if cache is enabled
     *
     * @return bool
     */
    public static function is_cache_enabled(): bool {
        return self::get_cache_ttl() > 0;
    }

    /**
     * Get cache TTL preset name
     *
     * @param int $ttl TTL value.
     * @return string
     */
    public static function get_ttl_preset_name(int $ttl): string {
        switch ($ttl) {
            case self::TTL_DISABLED:
                return 'disabled';
            case self::TTL_SHORT:
                return 'short';
            case self::TTL_MEDIUM:
                return 'medium';
            case self::TTL_LONG:
                return 'long';
            case self::TTL_DAY:
                return 'day';
            default:
                return 'custom';
        }
    }
}

<?php
/**
 * Plugin Constants
 * Centralized configuration for Powered by Panza plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PBA_Constants {

    /**
     * Plugin Information
     */
    const VERSION = '1.0.0';
    const PLUGIN_NAME = 'Powered by ATX';
    const TEXT_DOMAIN = 'powered-by-atx';
    const PLUGIN_PREFIX = 'pba_';

    /**
     * Default Settings
     */
    const DEFAULT_LOGO_URL = 'https://neilmallia.com/powered-by.png';
    const DEFAULT_TEXT = 'Powered by';
    const DEFAULT_LINK = 'https://neilmallia.com/';
    const DEFAULT_CACHE_TIMEOUT = HOUR_IN_SECONDS; // 1 hour
    const DEFAULT_WIDTH = '150';
    const DEFAULT_HEIGHT = 'auto';
    const DEFAULT_TARGET = '_blank';
    const DEFAULT_CLASS = 'powered-by-atx';

    /**
     * Security Settings
     */
    const REQUEST_TIMEOUT = 10; // seconds
    const SSL_VERIFY = true;
    const USER_AGENT = 'Powered by ATX Plugin/';

    /**
     * Allowed Image Types
     */
    const ALLOWED_IMAGE_TYPES = array(
        IMAGETYPE_GIF,
        IMAGETYPE_JPEG,
        IMAGETYPE_PNG,
        IMAGETYPE_WEBP
    );

    /**
     * Allowed Domains
     */
    const ALLOWED_DOMAINS = array(
        'neilmallia.com',
        'sancho.com.mt',
        'www.neilmallia.com',
        'www.sancho.com.mt',
    );

    /**
     * Cache Settings
     */
    const CACHE_KEY_PREFIX = 'powered_by_atx_image_';
    const CACHE_TRANSIENT_TIMEOUT = 'powered_by_atx_cache_cleared';

    /**
     * Admin Settings
     */
    const ADMIN_CAPABILITY = 'manage_options';
    const SETTINGS_PAGE_SLUG = 'powered-by-atx';
    const SETTINGS_MENU_TITLE = 'Powered by ATX';
    const SETTINGS_PAGE_TITLE = 'Powered by ATX Settings';
    const OPTIONS_GROUP = 'powered_by_atx_settings';

    /**
     * Options
     */
    const OPTION_LOGO_URL = 'pba_logo_url';
    const OPTION_TEXT = 'pba_text';
    const OPTION_CACHE_TIMEOUT = 'pba_cache_timeout';

    /**
     * Directory Paths
     */
    const ASSETS_DIR = 'assets/';
    const INCLUDES_DIR = 'includes/';

    /**
     * CSS File Names
     */
    const FRONTEND_CSS_FILE = 'frontend.css';
    const ADMIN_CSS_FILE = 'admin.css';

    /**
     * Get user agent string with version
     */
    public static function get_user_agent() {
        return self::USER_AGENT . self::VERSION;
    }

    /**
     * Get cache key for image
     */
    public static function get_cache_key( $image_url, $width, $height ) {
        return self::CACHE_KEY_PREFIX . md5( $image_url . $width . $height );
    }

    /**
     * Check if current user has admin capability
     */
    public static function current_user_can_admin() {
        return current_user_can( self::ADMIN_CAPABILITY );
    }

    /**
     * Get allowed image types as array
     */
    public static function get_allowed_image_types() {
        return self::ALLOWED_IMAGE_TYPES;
    }

    /**
     * Get allowed domains as array
     */
    public static function get_allowed_domains() {
        return self::ALLOWED_DOMAINS;
    }
}

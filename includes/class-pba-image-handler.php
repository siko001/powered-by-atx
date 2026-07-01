<?php
/**
 * Image Handler Class
 * Handles remote image fetching, caching, and validation
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PBA_Image_Handler {

    private $cache_timeout;

    public function __construct() {
        $this->cache_timeout = $this->get_cache_timeout();
    }

    /**
     * Get cached image HTML or fetch new one
     */
    public function get_image_html( $image_url, $width = '150', $height = 'auto' ) {
        $cache_key = PBA_Constants::get_cache_key( $image_url, $width, $height );
        $cached_image = get_transient( $cache_key );

        if ( $cached_image !== false ) {
            return $cached_image;
        }

        $image_html = $this->fetch_remote_image( $image_url, $width, $height );

        if ( $image_html ) {
            set_transient( $cache_key, $image_html, $this->cache_timeout );
        }

        return $image_html;
    }

    /**
     * Fetch remote image with validation
     */
    private function fetch_remote_image( $image_url, $width, $height ) {
        if ( ! filter_var( $image_url, FILTER_VALIDATE_URL ) || ! $this->is_allowed_domain( $image_url ) ) {
            return false;
        }

        $response = wp_remote_get( $image_url, array(
            'timeout'   => PBA_Constants::REQUEST_TIMEOUT,
            'sslverify' => PBA_Constants::SSL_VERIFY,
            'headers'   => array(
                'User-Agent' => PBA_Constants::get_user_agent(),
            ),
        ) );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return false;
        }

        $content_type = wp_remote_retrieve_header( $response, 'content-type' );
        if ( $content_type && strpos( $content_type, 'image/' ) !== 0 ) {
            return false;
        }

        $image_data = wp_remote_retrieve_body( $response );

        if ( ! $this->is_valid_image_data( $image_data ) ) {
            return false;
        }

        $width_attr  = ( $width !== 'auto' && is_numeric( $width ) ) ? sprintf( 'width="%d"', absint( $width ) ) : '';
        $height_attr = ( $height !== 'auto' && is_numeric( $height ) ) ? sprintf( 'height="%d"', absint( $height ) ) : '';

        return sprintf(
            '<img src="%s" alt="ATX Logo" %s %s loading="lazy" style="max-width:100%%;height:auto;">',
            esc_attr( $image_url ),
            $width_attr,
            $height_attr
        );
    }

    /**
     * Check if domain is allowed
     */
    private function is_allowed_domain( $url ) {
        $host = strtolower( parse_url( $url, PHP_URL_HOST ) );
        if ( ! $host ) {
            return false;
        }

        foreach ( PBA_Constants::get_allowed_domains() as $domain ) {
            $domain = strtolower( $domain );
            if ( $host === $domain || str_ends_with( $host, '.' . $domain ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate image data
     */
    private function is_valid_image_data( $image_data ) {
        if ( empty( $image_data ) ) {
            return false;
        }

        $image_info = getimagesizefromstring( $image_data );

        if ( $image_info === false ) {
            return false;
        }

        return in_array( $image_info[2], PBA_Constants::get_allowed_image_types(), true );
    }

    /**
     * Get cache timeout from settings
     */
    private function get_cache_timeout() {
        $timeout = get_option( PBA_Constants::OPTION_CACHE_TIMEOUT, PBA_Constants::DEFAULT_CACHE_TIMEOUT );
        return is_numeric( $timeout ) ? absint( $timeout ) : PBA_Constants::DEFAULT_CACHE_TIMEOUT;
    }

    /**
     * Clear all image caches
     */
    public function clear_cache() {
        global $wpdb;
        $prefix  = $wpdb->esc_like( '_transient_' . PBA_Constants::CACHE_KEY_PREFIX ) . '%';
        $timeout = $wpdb->esc_like( '_transient_timeout_' . PBA_Constants::CACHE_KEY_PREFIX ) . '%';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $prefix ) );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $timeout ) );
    }

    /**
     * Get default image URL
     */
    public function get_default_image_url() {
        return PBA_Constants::DEFAULT_LOGO_URL;
    }
}

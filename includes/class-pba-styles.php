<?php
/**
 * Styles Handler Class
 * Handles CSS and frontend styling
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PBA_Styles {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
    }

    /**
     * Enqueue stylesheets
     */
    public function enqueue_styles() {
        // Allow themes/plugins to dequeue if they want to handle styles themselves
        if ( ! apply_filters( 'pba_enqueue_default_styles', true ) ) {
            return;
        }

        wp_register_style(
            'powered-by-atx',
            POWERED_BY_ATX_PLUGIN_URL . PBA_Constants::ASSETS_DIR . PBA_Constants::FRONTEND_CSS_FILE,
            array(),
            PBA_Constants::VERSION
        );

        wp_enqueue_style( 'powered-by-atx' );
    }
}

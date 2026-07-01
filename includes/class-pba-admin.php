<?php
/**
 * Admin Class
 * Handles admin settings page and configuration
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PBA_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_action( 'admin_post_pba_clear_cache', array( $this, 'handle_clear_cache' ) );
    }

    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_options_page(
            PBA_Constants::SETTINGS_PAGE_TITLE,
            PBA_Constants::SETTINGS_MENU_TITLE,
            PBA_Constants::ADMIN_CAPABILITY,
            PBA_Constants::SETTINGS_PAGE_SLUG,
            array( $this, 'admin_page' )
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting( PBA_Constants::OPTIONS_GROUP, PBA_Constants::OPTION_LOGO_URL, array(
            'sanitize_callback' => 'esc_url_raw',
            'type'              => 'string',
        ) );
        register_setting( PBA_Constants::OPTIONS_GROUP, PBA_Constants::OPTION_TEXT, array(
            'sanitize_callback' => 'sanitize_text_field',
            'type'              => 'string',
        ) );
        register_setting( PBA_Constants::OPTIONS_GROUP, PBA_Constants::OPTION_CACHE_TIMEOUT, array(
            'sanitize_callback' => 'absint',
            'type'              => 'integer',
        ) );

        // Add settings sections
        add_settings_section(
            'pba_general_settings',
            'General Settings',
            array( $this, 'general_settings_callback' ),
            PBA_Constants::SETTINGS_PAGE_SLUG
        );

        add_settings_section(
            'pba_cache_management',
            'Cache Management',
            array( $this, 'cache_management_callback' ),
            PBA_Constants::SETTINGS_PAGE_SLUG
        );

        add_settings_section(
            'pba_usage_info',
            'Usage Information',
            array( $this, 'usage_info_callback' ),
            PBA_Constants::SETTINGS_PAGE_SLUG
        );

        // Add settings fields
        add_settings_field(
            'pba_logo_url',
            'Logo URL',
            array( $this, 'logo_url_field_callback' ),
            PBA_Constants::SETTINGS_PAGE_SLUG,
            'pba_general_settings'
        );

        add_settings_field(
            'pba_text',
            'Display Text',
            array( $this, 'text_field_callback' ),
            PBA_Constants::SETTINGS_PAGE_SLUG,
            'pba_general_settings'
        );

        add_settings_field(
            'pba_cache_timeout',
            'Cache Timeout',
            array( $this, 'cache_timeout_field_callback' ),
            PBA_Constants::SETTINGS_PAGE_SLUG,
            'pba_cache_management'
        );
    }

    /**
     * General settings section callback
     */
    public function general_settings_callback() {
        echo '<p>Configure the basic settings for your Powered by ATX branding.</p>';
    }

    /**
     * Cache management section callback
     */
    public function cache_management_callback() {
        echo '<p>Manage the image cache for optimal performance.</p>';
    }

    /**
     * Usage information section callback
     */
    public function usage_info_callback() {
        echo '<p>Learn how to use the shortcode and customize its appearance.</p>';
    }

    /**
     * Logo URL field callback
     */
    public function logo_url_field_callback() {
        $value = $this->get_option_logo_url();
        echo '<input type="url" id="' . PBA_Constants::OPTION_LOGO_URL . '" name="' . PBA_Constants::OPTION_LOGO_URL . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">URL to the logo image on your server. Must be from an approved domain.</p>';
    }

    /**
     * Text field callback
     */
    public function text_field_callback() {
        $value = $this->get_option_text();
        echo '<input type="text" id="' . PBA_Constants::OPTION_TEXT . '" name="' . PBA_Constants::OPTION_TEXT . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
        echo '<p class="description">Text to display alongside the logo.</p>';
    }

    /**
     * Cache timeout field callback
     */
    public function cache_timeout_field_callback() {
        $value = $this->get_option_cache_timeout();
        echo '<input type="number" id="' . PBA_Constants::OPTION_CACHE_TIMEOUT . '" name="' . PBA_Constants::OPTION_CACHE_TIMEOUT . '" value="' . esc_attr( $value ) . '" class="small-text" min="60" />';
        echo '<p class="description">How long to cache the image (in seconds). Default: ' . PBA_Constants::DEFAULT_CACHE_TIMEOUT . ' (1 hour).</p>';
    }

    /**
     * Admin page render
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( PBA_Constants::SETTINGS_PAGE_TITLE ); ?></h1>
            
            <div class="pba-admin-container">
                <div class="pba-settings-section">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields( PBA_Constants::OPTIONS_GROUP );
                        do_settings_sections( PBA_Constants::SETTINGS_PAGE_SLUG );
                        ?>
                        <?php submit_button( 'Save Settings' ); ?>
                    </form>
                </div>

                <div class="pba-info-section">
                    <?php $this->render_cache_management(); ?>
                    <?php $this->render_usage_info(); ?>
                    <?php $this->render_shortcode_examples(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render cache management section
     */
    private function render_cache_management() {
        ?>
        <div class="card">
            <h2>Cache Management</h2>
            <p>Clear the image cache when you update your logo or need to refresh the branding.</p>
            
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="pba_clear_cache">
                <?php wp_nonce_field( 'pba_clear_cache_nonce', 'pba_nonce' ); ?>
                <p>
                    <input type="submit" name="pba_clear_cache" class="button button-secondary" value="Clear Image Cache">
                    <span class="description">This will clear all cached images and force them to be re-fetched from your server.</span>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render usage information
     */
    private function render_usage_info() {
        ?>
        <div class="card">
            <h2>Quick Usage</h2>
            <p>Use the shortcode <code>[powered_by_atx]</code> in your posts, pages, or theme files.</p>
            
            <h3>Available Attributes:</h3>
            <ul>
                <li><code>text</code> - Custom text to display</li>
                <li><code>logo</code> - Custom logo URL</li>
                <li><code>link</code> - Custom link URL</li>
                <li><code>width</code> - Image width in pixels</li>
                <li><code>height</code> - Image height in pixels or "auto"</li>
                <li><code>class</code> - Custom CSS class</li>
                <li><code>target</code> - Link target (_blank, _self, _parent, _top)</li>
            </ul>
        </div>
        <?php
    }

    /**
     * Render shortcode examples
     */
    private function render_shortcode_examples() {
        ?>
        <div class="card">
            <h2>Shortcode Examples</h2>
            
            <h3>Basic Usage:</h3>
            <code>[powered_by_atx]</code>
            
            <h3>Custom Text:</h3>
            <code>[powered_by_atx text="Designed by ATX"]</code>
            
            <h3>Custom Dimensions:</h3>
            <code>[powered_by_atx width="200" height="60"]</code>
            
            <h3>Full Customization:</h3>
            <code>[powered_by_atx text="Website by ATX" width="250" height="50" class="my-branding" target="_self"]</code>
            
            <h3>PHP Usage:</h3>
            <code>&lt;?php echo do_shortcode('[powered_by_atx]'); ?&gt;</code>
        </div>
        <?php
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles( $hook ) {
        if ( 'settings_page_powered-by-atx' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'pba-admin',
            POWERED_BY_ATX_PLUGIN_URL . PBA_Constants::ASSETS_DIR . PBA_Constants::ADMIN_CSS_FILE,
            array(),
            PBA_Constants::VERSION
        );
    }

    /**
     * Get logo URL option
     */
    public function get_option_logo_url() {
        return get_option( PBA_Constants::OPTION_LOGO_URL, PBA_Constants::DEFAULT_LOGO_URL );
    }

    /**
     * Get text option
     */
    public function get_option_text() {
        return get_option( PBA_Constants::OPTION_TEXT, PBA_Constants::DEFAULT_TEXT );
    }

    /**
     * Handle cache clearing
     */
    public function handle_clear_cache() {
        // Verify nonce
        if ( ! isset( $_POST['pba_nonce'] ) || ! wp_verify_nonce( $_POST['pba_nonce'], 'pba_clear_cache_nonce' ) ) {
            wp_die( 'Security check failed.' );
        }

        // Check user capabilities
        if ( ! PBA_Constants::current_user_can_admin() ) {
            wp_die( 'You do not have sufficient permissions to perform this action.' );
        }

        // Clear cache
        $plugin = powered_by_atx();
        if ( $plugin && $plugin->get_image_handler() ) {
            $plugin->get_image_handler()->clear_cache();
        }

        // Set transient for admin notice
        set_transient( PBA_Constants::CACHE_TRANSIENT_TIMEOUT, true, 5 );

        // Redirect back to settings page
        wp_safe_redirect( admin_url( 'options-general.php?page=' . PBA_Constants::SETTINGS_PAGE_SLUG ) );
        exit;
    }

    /**
     * Get cache timeout option
     */
    public function get_option_cache_timeout() {
        $timeout = get_option( PBA_Constants::OPTION_CACHE_TIMEOUT, PBA_Constants::DEFAULT_CACHE_TIMEOUT );
        return is_numeric( $timeout ) ? absint( $timeout ) : PBA_Constants::DEFAULT_CACHE_TIMEOUT;
    }
}

<?php
/**
 * Plugin Name:       ATX Powered by
 * Plugin URI:        https://atx.com/
 * Description:       Display "Powered by ATX" branding with remote logo loading and caching.
 * Version:           1.0.0
 * Author:            ATX - Neil VM
 * Author URI:        https://sancho.com.mt/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       powered-by-atx
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Requires PHP:      8.1
 * Tested up to:      6.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Load constants first
require_once __DIR__ . '/includes/class-pba-constants.php';

define( 'POWERED_BY_ATX_VERSION', PBA_Constants::VERSION );
define( 'POWERED_BY_ATX_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'POWERED_BY_ATX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'POWERED_BY_ATX_INCLUDES_DIR', POWERED_BY_ATX_PLUGIN_DIR . PBA_Constants::INCLUDES_DIR );

/**
 * Autoloader for plugin classes
 */
function pba_autoloader( $class_name ) {
    // Only handle our plugin classes
    if ( strpos( $class_name, 'PBA_' ) !== 0 ) {
        return;
    }

    // Convert class name to file name
    $class_file = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
    $class_path = POWERED_BY_ATX_INCLUDES_DIR . $class_file;

    if ( file_exists( $class_path ) ) {
        require_once $class_path;
    }
}
spl_autoload_register( 'pba_autoloader' );

/**
 * Autoloader for namespaced plugin support classes.
 */
spl_autoload_register(
    function ( $class_name ) {
        $prefix = 'ATX\\PoweredByAtx\\';

        if ( strpos( $class_name, $prefix ) !== 0 ) {
            return;
        }

        $relative_class = substr( $class_name, strlen( $prefix ) );
        $class_path     = POWERED_BY_ATX_PLUGIN_DIR . 'src/' . str_replace( '\\', '/', $relative_class ) . '.php';

        if ( file_exists( $class_path ) ) {
            require_once $class_path;
        }
    }
);

/**
 * Main Plugin Class
 */
class Powered_By_Atx {

    private static $instance = null;
    private $image_handler;
    private $admin;
    private $styles;
    private $shortcode;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
    }

    /**
     * Initialize plugin components
     */
    public function init() {
        // Always initialize image handler and shortcode for frontend functionality
        $this->image_handler = new PBA_Image_Handler();
        $this->shortcode = new PBA_Shortcode( $this->image_handler );
        $this->styles = new PBA_Styles();

        // Only initialize admin components if user has admin capability
        if ( PBA_Constants::current_user_can_admin() ) {
            $this->admin = new PBA_Admin();
            $this->setup_hooks();
        }
        
        $this->load_textdomain();
    }

    /**
     * Setup plugin hooks
     */
    private function setup_hooks() {
        // Clear cache when settings are updated
        add_action( 'update_option_' . PBA_Constants::OPTION_LOGO_URL, array( $this, 'clear_cache' ) );
        add_action( 'update_option_' . PBA_Constants::OPTION_CACHE_TIMEOUT, array( $this, 'clear_cache' ) );

        // Add filter for allowed domains
        add_filter( 'pba_allowed_domains', array( $this, 'get_allowed_domains' ) );

        // Admin notices
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
    }

    /**
     * Load plugin text domain
     */
    private function load_textdomain() {
        load_plugin_textdomain(
            'powered-by-atx',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages'
        );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        if ( ! get_option( PBA_Constants::OPTION_LOGO_URL ) ) {
            add_option( PBA_Constants::OPTION_LOGO_URL, PBA_Constants::DEFAULT_LOGO_URL );
        }
        if ( ! get_option( PBA_Constants::OPTION_TEXT ) ) {
            add_option( PBA_Constants::OPTION_TEXT, PBA_Constants::DEFAULT_TEXT );
        }
        if ( ! get_option( PBA_Constants::OPTION_CACHE_TIMEOUT ) ) {
            add_option( PBA_Constants::OPTION_CACHE_TIMEOUT, PBA_Constants::DEFAULT_CACHE_TIMEOUT );
        }

        // Flush rewrite rules if needed
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear all caches
        if ( $this->image_handler ) {
            $this->image_handler->clear_cache();
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Clear all caches
     */
    public function clear_cache() {
        if ( $this->image_handler ) {
            $this->image_handler->clear_cache();
        }
    }

    /**
     * Get allowed domains filter
     */
    public function get_allowed_domains( $domains ) {
        return array_merge( $domains, PBa_Constants::get_allowed_domains() );
    }

    /**
     * Admin notices
     */
    public function admin_notices() {
        if ( get_transient( PBa_Constants::CACHE_TRANSIENT_TIMEOUT ) ) {
            printf(
                '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                esc_html__( 'Powered by ATX cache has been cleared.', PBa_Constants::TEXT_DOMAIN )
            );
            delete_transient( PBa_Constants::CACHE_TRANSIENT_TIMEOUT );
        }
    }

    /**
     * Get plugin version
     */
    public function get_version() {
        return POWERED_BY_ATX_VERSION;
    }

    /**
     * Get image handler instance
     */
    public function get_image_handler() {
        return $this->image_handler;
    }

    /**
     * Get admin instance
     */
    public function get_admin() {
        return $this->admin;
    }

    /**
     * Get styles instance
     */
    public function get_styles() {
        return $this->styles;
    }

    /**
     * Get shortcode instance
     */
    public function get_shortcode() {
        return $this->shortcode;
    }
}

// Initialize the plugin
function powered_by_atx() {
    return Powered_By_Atx::get_instance();
}

// Get the plugin running
powered_by_atx();

add_action(
    'plugins_loaded',
    function () {
        if ( is_admin() && class_exists( '\\ATX\\PoweredByAtx\\Support\\GitHubPluginUpdater' ) ) {
            ( new \ATX\PoweredByAtx\Support\GitHubPluginUpdater( __FILE__, __DIR__ ) )->register();
        }
    }
);

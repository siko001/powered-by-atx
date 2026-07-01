<?php
/**
 * Shortcode Handler Class
 * Handles shortcode processing and rendering
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PBA_Shortcode {

    private $image_handler;

    public function __construct( $image_handler ) {
        $this->image_handler = $image_handler;
        add_shortcode( 'powered_by_atx', array( $this, 'render_shortcode' ) );
    }

    /**
     * Render the shortcode
     */
    public function render_shortcode( $atts ) {
        $atts = $this->parse_attributes( $atts );
        $atts = $this->sanitize_attributes( $atts );

        // Get cached image or fetch new one
        $image_html = $this->get_image_html( $atts );

        if ( ! $image_html ) {
            return $this->get_fallback_output( $atts );
        }

        return $this->build_output( $atts, $image_html );
    }

    /**
     * Parse and set default attributes
     */
    private function parse_attributes( $atts ) {
        return shortcode_atts( array(
            'text' => $this->get_option_text(),
            'logo' => $this->get_option_logo_url(),
            'link' => PBA_Constants::DEFAULT_LINK,
            'target' => PBA_Constants::DEFAULT_TARGET,
            'class' => PBA_Constants::DEFAULT_CLASS,
            'width' => PBA_Constants::DEFAULT_WIDTH,
            'height' => PBA_Constants::DEFAULT_HEIGHT,
            'color' => '',
            'show_text' => '1',
            'show_logo' => '1'
        ), $atts, 'powered_by_atx' );
    }

    /**
     * Sanitize all attributes
     */
    private function sanitize_attributes( $atts ) {
        return array(
            'text' => sanitize_text_field( $atts['text'] ),
            'logo' => esc_url_raw( $atts['logo'] ),
            'link' => esc_url_raw( $atts['link'] ),
            'target' => $this->sanitize_target( $atts['target'] ),
            'class' => sanitize_html_class( $atts['class'] ),
            'width' => $this->sanitize_dimension( $atts['width'], 150 ),
            'height' => $this->sanitize_dimension( $atts['height'], 'auto' ),
            'color' => $this->sanitize_color( $atts['color'] ),
            'show_text' => $this->sanitize_boolean( $atts['show_text'] ),
            'show_logo' => $this->sanitize_boolean( $atts['show_logo'] )
        );
    }

    /**
     * Sanitize target attribute
     */
    private function sanitize_target( $target ) {
        $allowed_targets = array( '_blank', '_self', '_parent', '_top' );
        return in_array( $target, $allowed_targets ) ? $target : '_blank';
    }

    /**
     * Sanitize dimension (width/height)
     */
    private function sanitize_dimension( $dimension, $default ) {
        if ( $dimension === 'auto' ) {
            return 'auto';
        }
        
        return is_numeric( $dimension ) ? absint( $dimension ) : $default;
    }

    /**
     * Sanitize boolean values
     */
    private function sanitize_boolean( $value ) {
        return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
    }

    /**
     * Sanitize color value
     */
    private function sanitize_color( $color ) {
        if ( empty( $color ) ) {
            return '';
        }
        
        // Remove any whitespace
        $color = trim( $color );
        
        // Validate hex color (3, 4, 6, or 8 digits)
        if ( preg_match( '/^#([A-Fa-f0-9]{3,4}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/', $color ) ) {
            return $color;
        }
        
        // Validate rgb/rgba colors
        if ( preg_match( '/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*(?:,\s*([01]?\.\d+)\s*)?\)$/', $color ) ) {
            return $color;
        }
        
        // Validate hsl/hsla colors
        if ( preg_match( '/^hsla?\(\s*(\d{1,3})\s*,\s*(\d{1,3})%\s*,\s*(\d{1,3})%\s*(?:,\s*([01]?\.\d+)\s*)?\)$/', $color ) ) {
            return $color;
        }
        
        // Validate basic CSS color names
        $valid_colors = array(
            'black', 'silver', 'gray', 'white', 'maroon', 'red', 'purple', 'fuchsia',
            'green', 'lime', 'olive', 'yellow', 'navy', 'blue', 'teal', 'aqua',
            'orange', 'aliceblue', 'antiquewhite', 'aquamarine', 'azure', 'beige',
            'bisque', 'blanchedalmond', 'blueviolet', 'brown', 'burlywood', 'cadetblue',
            'chartreuse', 'chocolate', 'coral', 'cornflowerblue', 'cornsilk', 'crimson',
            'cyan', 'darkblue', 'darkcyan', 'darkgoldenrod', 'darkgray', 'darkgreen',
            'darkgrey', 'darkkhaki', 'darkmagenta', 'darkolivegreen', 'darkorange',
            'darkorchid', 'darkred', 'darksalmon', 'darkseagreen', 'darkslateblue',
            'darkslategray', 'darkslategrey', 'darkturquoise', 'darkviolet', 'deeppink',
            'deepskyblue', 'dimgray', 'dimgrey', 'dodgerblue', 'firebrick', 'floralwhite',
            'forestgreen', 'gainsboro', 'ghostwhite', 'gold', 'goldenrod', 'greenyellow',
            'grey', 'honeydew', 'hotpink', 'indianred', 'indigo', 'ivory', 'khaki',
            'lavender', 'lavenderblush', 'lawngreen', 'lemonchiffon', 'lightblue', 'lightcoral',
            'lightcyan', 'lightgoldenrodyellow', 'lightgray', 'lightgreen', 'lightgrey',
            'lightpink', 'lightsalmon', 'lightseagreen', 'lightskyblue', 'lightslategray',
            'lightslategrey', 'lightsteelblue', 'lightyellow', 'limegreen', 'linen',
            'magenta', 'mediumaquamarine', 'mediumblue', 'mediumorchid', 'mediumpurple',
            'mediumseagreen', 'mediumslateblue', 'mediumspringgreen', 'mediumturquoise',
            'mediumvioletred', 'midnightblue', 'mintcream', 'mistyrose', 'moccasin',
            'navajowhite', 'oldlace', 'olivedrab', 'orangered', 'orchid', 'palegoldenrod',
            'palegreen', 'paleturquoise', 'palevioletred', 'papayawhip', 'peachpuff',
            'peru', 'pink', 'plum', 'powderblue', 'rosybrown', 'royalblue', 'saddlebrown',
            'salmon', 'sandybrown', 'seagreen', 'seashell', 'sienna', 'skyblue', 'slateblue',
            'slategray', 'slategrey', 'snow', 'springgreen', 'steelblue', 'tan', 'thistle',
            'tomato', 'turquoise', 'violet', 'wheat', 'whitesmoke', 'yellowgreen'
        );
        
        if ( in_array( strtolower( $color ), $valid_colors ) ) {
            return $color;
        }
        
        return ''; // Invalid color, return empty
    }

    /**
     * Get image HTML
     */
    private function get_image_html( $atts ) {
        if ( ! $atts['show_logo'] ) {
            return '';
        }

        return $this->image_handler->get_image_html( $atts['logo'], $atts['width'], $atts['height'] );
    }

    /**
     * Build the final output
     */
    private function build_output( $atts, $image_html ) {
        $content = '';

        // Add text if enabled
        if ( $atts['show_text'] && ! empty( $atts['text'] ) ) {
            $style = ! empty( $atts['color'] ) ? sprintf( ' style="color: %s;"', esc_attr( $atts['color'] ) ) : '';
            $content .= sprintf( '<span%s>%s</span>', $style, esc_html( $atts['text'] ) );
        }

        // Add logo if enabled
        if ( $atts['show_logo'] && $image_html ) {
            $content .= $image_html;
        }

        // Wrap in link if we have content
        if ( ! empty( $content ) ) {
            $content = sprintf(
                '<a href="%s" target="%s" rel="noopener noreferrer">%s</a>',
                esc_url( $atts['link'] ),
                esc_attr( $atts['target'] ),
                $content
            );
        }

        // Wrap in container div
        if ( ! empty( $content ) ) {
            $content = sprintf(
                '<div class="%s">%s</div>',
                esc_attr( $atts['class'] ),
                $content
            );
        }

        return $content;
    }

    /**
     * Get fallback output when image fails
     */
    private function get_fallback_output( $atts ) {
        // If text is enabled and we have text, show text-only version
        if ( $atts['show_text'] && ! empty( $atts['text'] ) ) {
            return sprintf(
                '<div class="%s"><a href="%s" target="%s" rel="noopener noreferrer"><span>%s</span></a></div>',
                esc_attr( $atts['class'] ),
                esc_url( $atts['link'] ),
                esc_attr( $atts['target'] ),
                esc_html( $atts['text'] )
            );
        }

        return '';
    }

    /**
     * Get logo URL from options
     */
    private function get_option_logo_url() {
        return get_option( PBA_Constants::OPTION_LOGO_URL, $this->image_handler->get_default_image_url() );
    }

    /**
     * Get text from options
     */
    private function get_option_text() {
        return get_option( PBA_Constants::OPTION_TEXT, PBA_Constants::DEFAULT_TEXT );
    }

    
    /**
     * Get supported attributes for documentation
     */
    public function get_supported_attributes() {
        return array(
            'text' => 'Custom text to display',
            'logo' => 'Custom logo URL',
            'link' => 'Custom link URL',
            'target' => 'Link target (_blank, _self, _parent, _top)',
            'class' => 'Custom CSS class',
            'width' => 'Image width in pixels',
            'height' => 'Image height in pixels or "auto"',
            'color' => 'Text color (hex, rgb, hsl, or color name)',
            'show_text' => 'Show/hide text (1/0)',
            'show_logo' => 'Show/hide logo (1/0)'
        );
    }

    /**
     * Validate shortcode attributes
     */
    public function validate_attributes( $atts ) {
        $errors = array();

        if ( isset( $atts['logo'] ) && ! empty( $atts['logo'] ) ) {
            if ( ! filter_var( $atts['logo'], FILTER_VALIDATE_URL ) ) {
                $errors[] = 'Invalid logo URL';
            }
        }

        if ( isset( $atts['link'] ) && ! empty( $atts['link'] ) ) {
            if ( ! filter_var( $atts['link'], FILTER_VALIDATE_URL ) ) {
                $errors[] = 'Invalid link URL';
            }
        }

        if ( isset( $atts['width'] ) && ! is_numeric( $atts['width'] ) && $atts['width'] !== 'auto' ) {
            $errors[] = 'Width must be a number or "auto"';
        }

        if ( isset( $atts['height'] ) && ! is_numeric( $atts['height'] ) && $atts['height'] !== 'auto' ) {
            $errors[] = 'Height must be a number or "auto"';
        }

        return $errors;
    }
}

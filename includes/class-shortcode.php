<?php

class SGS_Shortcode {

    // Pin to a specific minor version for production stability; bump intentionally when upgrading.
    const ALPINE_CDN = 'https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js';

    public function __construct() {
        add_shortcode( 'small-group-search-v2', [ $this, 'render' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
        add_filter( 'script_loader_tag', [ $this, 'add_alpine_defer' ], 10, 2 );
    }

    public function register_assets(): void {
        wp_register_script( 'sgs-alpine', self::ALPINE_CDN, [], null, true );
    }

    /** Alpine must be loaded with defer so it initialises after the component function is defined. */
    public function add_alpine_defer( string $tag, string $handle ): string {
        if ( $handle === 'sgs-alpine' && strpos( $tag, 'defer' ) === false ) {
            return str_replace( '<script ', '<script defer ', $tag );
        }
        return $tag;
    }

    public function render( $atts ): string {
        $groups = SGS_Snapshot_CPT::get_active_groups();

        if ( empty( $groups ) ) {
            return '<p>No groups are currently available. Please check back soon.</p>';
        }

        wp_enqueue_script( 'sgs-alpine' );

        $json = json_encode( [ 'groups' => $groups ], JSON_HEX_TAG | JSON_UNESCAPED_UNICODE );
        $js   = file_get_contents( SGS_DIR . 'assets/small-groups.js' );

        ob_start();
        echo '<script>window.sgsData = ' . $json . ';' . $js . '</script>';
        include SGS_DIR . 'templates/search.php';
        return ob_get_clean();
    }
}

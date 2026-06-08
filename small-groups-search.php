<?php
/**
 * Plugin Name: Small Groups Search

 * Description: Small group search shortcode with CSV upload and snapshot history.
 * Version:     2.0.0
 * Author:      Morgan Stone
 * License:     GPL-2.0-or-later
 * Text Domain: small-groups-search
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SGS_VERSION', '2.0.0' );
define( 'SGS_DIR', plugin_dir_path( __FILE__ ) );
define( 'SGS_URL', plugin_dir_url( __FILE__ ) );

require_once SGS_DIR . 'includes/class-csv-validator.php';
require_once SGS_DIR . 'includes/class-csv-parser.php';
require_once SGS_DIR . 'includes/class-snapshot-cpt.php';
require_once SGS_DIR . 'includes/class-shortcode.php';

if ( is_admin() ) {
    require_once SGS_DIR . 'admin/class-admin-page.php';
    new SGS_Admin_Page();
}

register_activation_hook( __FILE__, function () {
    SGS_Snapshot_CPT::register();
    flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

new SGS_Snapshot_CPT();
new SGS_Shortcode();

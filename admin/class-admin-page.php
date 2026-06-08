<?php

class SGS_Admin_Page {

    const MENU_SLUG = 'sgs-small-groups';

    public function __construct() {
        add_action( 'admin_menu',            [ $this, 'add_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_styles' ] );
        add_action( 'admin_post_sgs_upload',     [ $this, 'handle_upload' ] );
        add_action( 'admin_post_sgs_activate',   [ $this, 'handle_activate' ] );
        add_action( 'admin_post_sgs_deactivate', [ $this, 'handle_deactivate' ] );
        add_action( 'admin_post_sgs_delete',     [ $this, 'handle_delete' ] );
    }

    public function add_menu(): void {
        add_menu_page(
            'Small Groups',
            'Small Groups',
            'manage_options',
            self::MENU_SLUG,
            [ $this, 'render_page' ],
            'dashicons-groups',
            30
        );
    }

    public function enqueue_styles( string $hook ): void {
        if ( strpos( $hook, self::MENU_SLUG ) === false ) return;
        wp_enqueue_style( 'sgs-admin', SGS_URL . 'admin/admin.css', [], SGS_VERSION );
    }

    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $snapshots   = SGS_Snapshot_CPT::all();
        $notice      = isset( $_GET['sgs_notice'] )      ? sanitize_text_field( rawurldecode( $_GET['sgs_notice'] ) )      : null;
        $notice_type = isset( $_GET['sgs_notice_type'] ) ? sanitize_text_field( $_GET['sgs_notice_type'] ) : 'info';
        include SGS_DIR . 'admin/views/page.php';
    }

    public function handle_upload(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        check_admin_referer( 'sgs_upload' );

        $file = $_FILES['csv_file'] ?? null;
        if ( ! $file || $file['error'] !== UPLOAD_ERR_OK ) {
            $this->redirect( 'Upload failed — no file received.', 'error' );
        }
        if ( strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) ) !== 'csv' ) {
            $this->redirect( 'Only .csv files are accepted.', 'error' );
        }

        $tmp      = $file['tmp_name'];
        $headers  = SGS_CSV_Parser::get_headers( $tmp );
        $warnings = SGS_CSV_Validator::validate( $headers );
        $groups   = SGS_CSV_Parser::parse( $tmp );
        $snap_id  = SGS_Snapshot_CPT::save( $groups, $warnings );

        if ( is_wp_error( $snap_id ) ) {
            $this->redirect( 'Failed to save snapshot: ' . $snap_id->get_error_message(), 'error' );
        }

        $activate = ! empty( $_POST['sgs_activate'] );
        if ( $activate || ! get_option( SGS_Snapshot_CPT::ACTIVE_OPT ) ) {
            SGS_Snapshot_CPT::activate( $snap_id );
        }

        $msg  = sprintf( '%d groups imported.', count( $groups ) );
        $type = 'success';
        if ( ! empty( $warnings ) ) {
            $msg .= sprintf( ' %d schema warning(s) — review the history table below.', count( $warnings ) );
            $type = 'warning';
        }
        $this->redirect( $msg, $type );
    }

    public function handle_activate(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        check_admin_referer( 'sgs_activate' );
        $id = (int) ( $_POST['snapshot_id'] ?? 0 );
        if ( $id ) SGS_Snapshot_CPT::activate( $id );
        $this->redirect( 'Snapshot activated.', 'success' );
    }

    public function handle_deactivate(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        check_admin_referer( 'sgs_deactivate' );
        SGS_Snapshot_CPT::deactivate();
        $this->redirect( 'Groups are now offline. No snapshot is active.', 'success' );
    }

    public function handle_delete(): void {
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        check_admin_referer( 'sgs_delete' );
        $id = (int) ( $_POST['snapshot_id'] ?? 0 );
        if ( $id && ! SGS_Snapshot_CPT::delete( $id ) ) {
            $this->redirect( 'Cannot delete the active snapshot. Activate a different snapshot first.', 'error' );
        }
        $this->redirect( 'Snapshot deleted.', 'success' );
    }

    private function redirect( string $notice, string $type ): void {
        wp_redirect( add_query_arg( [
            'page'            => self::MENU_SLUG,
            'sgs_notice'      => rawurlencode( $notice ),
            'sgs_notice_type' => $type,
        ], admin_url( 'admin.php' ) ) );
        exit;
    }
}

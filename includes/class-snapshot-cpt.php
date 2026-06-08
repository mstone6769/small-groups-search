<?php

class SGS_Snapshot_CPT {

    const POST_TYPE  = 'sgs_snapshot';
    const ACTIVE_OPT = 'sgs_active_snapshot_id';

    public function __construct() {
        add_action( 'init', [ 'SGS_Snapshot_CPT', 'register' ] );
    }

    public static function register(): void {
        register_post_type( self::POST_TYPE, [
            'labels'   => [
                'name'          => 'Group Snapshots',
                'singular_name' => 'Snapshot',
            ],
            'public'   => false,
            'show_ui'  => false,
            'supports' => [ 'title' ],
        ] );
    }

    /** Save a new snapshot and return its post ID (or WP_Error on failure). */
    public static function save( array $groups, array $warnings = [] ): int|\WP_Error {
        $id = wp_insert_post( [
            'post_type'   => self::POST_TYPE,
            'post_title'  => current_time( 'Y-m-d H:i:s' ),
            'post_status' => 'publish',
        ] );

        if ( is_wp_error( $id ) ) return $id;

        update_post_meta( $id, '_sgs_groups',   wp_json_encode( $groups ) );
        update_post_meta( $id, '_sgs_count',    count( $groups ) );
        update_post_meta( $id, '_sgs_warnings', $warnings );

        return $id;
    }

    /** Make a snapshot the live one shown by the shortcode. */
    public static function activate( int $id ): void {
        update_option( self::ACTIVE_OPT, $id );
    }

    /** Return the groups array from the currently active snapshot. */
    public static function get_active_groups(): array {
        $id   = (int) get_option( self::ACTIVE_OPT, 0 );
        if ( ! $id ) return [];
        $json = get_post_meta( $id, '_sgs_groups', true );
        return json_decode( $json, true ) ?? [];
    }

    /** Return metadata for all snapshots, newest first. */
    public static function all(): array {
        $posts     = get_posts( [
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );
        $active_id = (int) get_option( self::ACTIVE_OPT, 0 );

        return array_map( fn( $p ) => [
            'id'       => $p->ID,
            'date'     => $p->post_title,
            'count'    => (int)   get_post_meta( $p->ID, '_sgs_count',    true ),
            'warnings' => (array) get_post_meta( $p->ID, '_sgs_warnings', true ),
            'active'   => $p->ID === $active_id,
        ], $posts );
    }

    /** Permanently delete a snapshot. Refuses to delete the active one. */
    public static function delete( int $id ): void {
        if ( $id === (int) get_option( self::ACTIVE_OPT, 0 ) ) return;
        wp_delete_post( $id, true );
    }
}

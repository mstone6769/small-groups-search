<?php

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class SnapshotCptTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    // ── save() ───────────────────────────────────────────────────────────────

    public function test_save_propagates_wp_error(): void {
        $error = new WP_Error( 'db_error', 'Insert failed' );
        Functions\when( 'current_time' )->justReturn( '2024-01-01 12:00:00' );
        Functions\when( 'wp_insert_post' )->justReturn( $error );
        Functions\when( 'is_wp_error' )->alias( fn( $v ) => $v instanceof WP_Error );
        Functions\expect( 'update_post_meta' )->never();

        $result = SGS_Snapshot_CPT::save( [['name' => 'Alpha']] );
        $this->assertSame( $error, $result );
    }

    public function test_save_returns_post_id_on_success(): void {
        Functions\when( 'current_time' )->justReturn( '2024-01-01 12:00:00' );
        Functions\when( 'wp_insert_post' )->justReturn( 7 );
        Functions\when( 'is_wp_error' )->justReturn( false );
        Functions\when( 'update_post_meta' )->justReturn( true );

        $result = SGS_Snapshot_CPT::save( [['name' => 'Alpha']], ['a warning'], 'file.csv' );
        $this->assertSame( 7, $result );
    }

    public function test_save_stores_filename_in_meta(): void {
        Functions\when( 'current_time' )->justReturn( '2024-01-01 12:00:00' );
        Functions\when( 'wp_insert_post' )->justReturn( 7 );
        Functions\when( 'is_wp_error' )->justReturn( false );

        $calls = [];
        Functions\when( 'update_post_meta' )->alias( function ( $id, $key, $value ) use ( &$calls ) {
            $calls[ $key ] = $value;
            return true;
        } );

        SGS_Snapshot_CPT::save( [['name' => 'Alpha']], [], 'my-groups.csv' );
        $this->assertSame( 'my-groups.csv', $calls['_sgs_filename'] );
    }

    public function test_save_defaults_to_empty_filename(): void {
        Functions\when( 'current_time' )->justReturn( '2024-01-01 12:00:00' );
        Functions\when( 'wp_insert_post' )->justReturn( 5 );
        Functions\when( 'is_wp_error' )->justReturn( false );

        $calls = [];
        Functions\when( 'update_post_meta' )->alias( function ( $id, $key, $value ) use ( &$calls ) {
            $calls[ $key ] = $value;
            return true;
        } );

        SGS_Snapshot_CPT::save( [] );
        $this->assertSame( '', $calls['_sgs_filename'] );
    }

    public function test_save_stores_group_count_in_meta(): void {
        Functions\when( 'current_time' )->justReturn( '2024-01-01 12:00:00' );
        Functions\when( 'wp_insert_post' )->justReturn( 9 );
        Functions\when( 'is_wp_error' )->justReturn( false );

        $calls = [];
        Functions\when( 'update_post_meta' )->alias( function ( $id, $key, $value ) use ( &$calls ) {
            $calls[ $key ] = $value;
            return true;
        } );

        SGS_Snapshot_CPT::save( [['name' => 'A'], ['name' => 'B'], ['name' => 'C']] );
        $this->assertSame( 3, $calls['_sgs_count'] );
    }

    // ── all() ─────────────────────────────────────────────────────────────────

    public function test_all_returns_empty_array_when_no_posts(): void {
        Functions\when( 'get_posts' )->justReturn( [] );
        Functions\when( 'get_option' )->justReturn( 0 );
        $this->assertSame( [], SGS_Snapshot_CPT::all() );
    }

    public function test_all_maps_snapshot_fields_correctly(): void {
        $post             = new stdClass();
        $post->ID         = 10;
        $post->post_title = '2024-06-01 09:00:00';

        Functions\when( 'get_posts' )->justReturn( [$post] );
        Functions\when( 'get_option' )->justReturn( 0 );
        Functions\when( 'get_post_meta' )->alias( function ( $id, $key ) {
            return match ( $key ) {
                '_sgs_filename' => 'groups.csv',
                '_sgs_count'    => 8,
                '_sgs_warnings' => ['Missing column'],
                default         => '',
            };
        } );

        $result = SGS_Snapshot_CPT::all();
        $this->assertCount( 1, $result );
        $snap = $result[0];
        $this->assertSame( 10,                  $snap['id'] );
        $this->assertSame( '2024-06-01 09:00:00', $snap['date'] );
        $this->assertSame( 'groups.csv',         $snap['filename'] );
        $this->assertSame( 8,                    $snap['count'] );
        $this->assertSame( ['Missing column'],   $snap['warnings'] );
        $this->assertFalse( $snap['active'] );
    }

    public function test_all_marks_only_the_active_snapshot(): void {
        $post1             = new stdClass();
        $post1->ID         = 10;
        $post1->post_title = '2024-06-01 09:00:00';

        $post2             = new stdClass();
        $post2->ID         = 20;
        $post2->post_title = '2024-06-02 09:00:00';

        Functions\when( 'get_posts' )->justReturn( [$post1, $post2] );
        Functions\when( 'get_option' )->justReturn( 20 );
        Functions\when( 'get_post_meta' )->alias( function ( $id, $key ) {
            return match ( $key ) {
                '_sgs_warnings' => [],
                default         => '',
            };
        } );

        $result = SGS_Snapshot_CPT::all();
        $this->assertFalse( $result[0]['active'] ); // ID 10
        $this->assertTrue(  $result[1]['active'] ); // ID 20
    }

    // ── get_active_groups() ───────────────────────────────────────────────────

    public function test_returns_empty_array_when_no_active_snapshot(): void {
        Functions\when('get_option')->justReturn(0);
        $this->assertSame([], SGS_Snapshot_CPT::get_active_groups());
    }

    public function test_returns_empty_array_when_meta_is_not_an_array(): void {
        Functions\when('get_option')->justReturn(5);
        Functions\when('get_post_meta')->justReturn('broken');
        $this->assertSame([], SGS_Snapshot_CPT::get_active_groups());
    }

    public function test_returns_empty_array_when_meta_is_false(): void {
        Functions\when('get_option')->justReturn(5);
        Functions\when('get_post_meta')->justReturn(false);
        $this->assertSame([], SGS_Snapshot_CPT::get_active_groups());
    }

    public function test_returns_groups_from_active_snapshot(): void {
        $groups = [
            ['name' => 'Alpha', 'leaders' => 'Jane'],
            ['name' => 'Beta',  'leaders' => 'John'],
        ];
        Functions\when('get_option')->justReturn(7);
        Functions\when('get_post_meta')->justReturn($groups);
        $this->assertSame($groups, SGS_Snapshot_CPT::get_active_groups());
    }

    // ── delete() ─────────────────────────────────────────────────────────────

    public function test_delete_refuses_active_snapshot_and_returns_false(): void {
        Functions\when('get_option')->justReturn(42);
        Functions\expect('wp_delete_post')->never();
        $this->assertFalse(SGS_Snapshot_CPT::delete(42));
    }

    public function test_delete_removes_inactive_snapshot_and_returns_true(): void {
        Functions\when('get_option')->justReturn(42);
        Functions\expect('wp_delete_post')->once()->with(99, true);
        $this->assertTrue(SGS_Snapshot_CPT::delete(99));
    }

    // ── activate() ───────────────────────────────────────────────────────────

    public function test_activate_updates_the_active_option(): void {
        Functions\expect('update_option')
            ->once()
            ->with(SGS_Snapshot_CPT::ACTIVE_OPT, 13);
        SGS_Snapshot_CPT::activate(13);
        $this->addToAssertionCount(1);
    }

    // ── deactivate() ─────────────────────────────────────────────────────────

    public function test_deactivate_deletes_the_active_option(): void {
        Functions\expect('delete_option')
            ->once()
            ->with(SGS_Snapshot_CPT::ACTIVE_OPT);
        SGS_Snapshot_CPT::deactivate();
        $this->addToAssertionCount(1);
    }
}

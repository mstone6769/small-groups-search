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
}

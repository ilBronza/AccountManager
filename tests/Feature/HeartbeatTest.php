<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class HeartbeatTest extends TestCase
{
    public function test_guests_cannot_set_presence(): void
    {
        $this->postJson(config('accountmanager.heartbeat.uri'), ['user_id' => 42])->assertUnauthorized();
        $this->assertFalse(Cache::has('heartbeat-42'));
    }

    public function test_presence_uses_session_identity_and_expires(): void
    {
        $user = $this->makeUserWithRole('editor');
        $this->actingAs($user)->postJson(config('accountmanager.heartbeat.uri'), ['user_id' => 42])->assertOk();
        $this->assertTrue(Cache::has('heartbeat-' . $user->id));
        $this->assertFalse(Cache::has('heartbeat-42'));
        $this->travel(61)->seconds();
        $this->assertFalse(Cache::has('heartbeat-' . $user->id));
    }

    public function test_disabled_heartbeat_is_unavailable(): void
    {
        config(['accountmanager.heartbeat.enabled' => false]);
        $user = $this->makeUserWithRole('editor');
        $this->actingAs($user)->postJson(config('accountmanager.heartbeat.uri'))->assertNotFound();
        $this->assertFalse(Cache::has('heartbeat-' . $user->id));
    }

    public function test_heartbeat_is_rate_limited(): void
    {
        $this->actingAs($this->makeUserWithRole('editor'));
        for ($i = 0; $i < 6; $i++) {
            $this->postJson(config('accountmanager.heartbeat.uri'))->assertOk();
        }
        $this->postJson(config('accountmanager.heartbeat.uri'))->assertStatus(429);
    }
}

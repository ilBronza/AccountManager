<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

class HeartbeatTest extends TestCase
{
	public function test_heartbeat_sets_cache_without_auth(): void
	{
		$uri = config('accountmanager.heartbeat.uri');

		$response = $this->postJson($uri, ['user_id' => 42]);

		$response->assertOk()->assertJson(['ok' => true]);
		$this->assertTrue(Cache::has('heartbeat-42'));
	}

	public function test_heartbeat_returns_422_without_user_id(): void
	{
		$uri = config('accountmanager.heartbeat.uri');

		$response = $this->postJson($uri, []);

		$response->assertStatus(422)->assertJson(['ok' => false]);
	}
}

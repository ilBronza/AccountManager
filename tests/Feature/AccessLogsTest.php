<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Http\Middleware\LogUserAccess;
use IlBronza\AccountManager\Models\UserAccessLog;
use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Http\Request;

class AccessLogsTest extends TestCase
{
    public function test_access_log_omits_query_credentials(): void
    {
        $user = $this->makeUserWithRole('editor');
        $request = Request::create('/auth/callback?code=secret&token=secret', 'GET');
        $request->setUserResolver(fn () => $user);
        (new LogUserAccess)->terminate($request, response('ok'));
        $log = UserAccessLog::firstOrFail();
        $this->assertSame('http://localhost/auth/callback', $log->url);
    }

    public function test_pruning_preserves_recent_logs_and_rejects_invalid_retention(): void
    {
        foreach ([100, 10] as $days) {
            UserAccessLog::create([
                'user_id' => 1, 'visited_at' => now()->subDays($days), 'method' => 'GET',
                'url' => 'http://localhost/', 'ip' => '127.0.0.1',
            ]);
        }
        $this->artisan('accountmanager:prune-access-logs', ['--days' => 0])->assertFailed();
        $this->assertDatabaseCount('user_access_logs', 2);
        $this->artisan('accountmanager:prune-access-logs')->assertSuccessful();
        $this->assertDatabaseCount('user_access_logs', 1);
        $this->assertTrue(UserAccessLog::first()->visited_at->greaterThan(now()->subDays(11)));
    }
}

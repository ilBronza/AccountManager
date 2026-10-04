<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Http\Middleware\AuthenticateWithIp;
use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;

class AccessRulesTest extends TestCase
{
    public function test_disabled_default_role_and_explicit_empty_rule_deny_access(): void
    {
        $this->actingAs($this->makeUserWithRole('accountManagerUsers'));
        config(['accountmanager.defaultRoles' => ['superadmin' => true, 'accountManagerUsers' => false]]);
        $this->get('/account-management/users/create-slim')->assertForbidden();
        config(['accountmanager.defaultRoles' => ['accountManagerUsers']]);
        $this->get('/account-management/users/create-slim')->assertOk();
        config(['accountmanager.routeRoles.accountmanagerusers.createSlim' => []]);
        $this->get('/account-management/users/create-slim')->assertForbidden();
    }

    public function test_suspended_user_is_logged_out_and_cannot_set_presence(): void
    {
        $this->makeUserWithRole('superadmin');
        $user = $this->makeUserWithRole('editor', 'suspended@example.test');
        $user->active = false;
        $user->save();
        $this->actingAs($user)->postJson('/account-management/heartbeat')->assertForbidden();
        $this->assertGuest();
        $this->assertFalse(Cache::has('heartbeat-' . $user->id));
    }

    public static function ipCases(): array
    {
        return [
            'internal' => ['192.168.1.5', false, [], null, 200],
            'remote denied' => ['203.0.113.10', false, [], null, 403],
            'remote unrestricted' => ['203.0.113.10', true, [], null, 200],
            'user CIDR match' => ['203.0.113.10', true, [['ip' => '203.0.113.0/24']], null, 200],
            'user CIDR mismatch' => ['198.51.100.1', true, [['ip' => '203.0.113.0/24']], null, 403],
            'IPv6' => ['2001:db8::1', true, [['ip' => '2001:db8::/32']], null, 200],
            'role text' => ['198.51.100.1', true, [['ip' => '203.0.113.0/24']], "198.51.100.0/24\n192.0.2.1", 200],
            'role cannot override disabled remote' => ['198.51.100.1', false, [], '198.51.100.0/24', 403],
            'role mismatch' => ['198.51.100.1', true, [['ip' => '203.0.113.0/24']], '192.0.2.1', 403],
            'invalid value' => ['198.51.100.1', true, [['ip' => 'invalid']], null, 403],
        ];
    }

    #[DataProvider('ipCases')]
    public function test_ip_access(string $ip, bool $remote, array $ips, ?string $roleIps, int $status): void
    {
        Route::middleware(['web', AuthenticateWithIp::class])->get('/test-ip', fn () => 'ok');
        $user = $this->makeUserWithRole('editor');
        $user->forceFill(['allow_from_remote' => $remote, 'allowed_ips' => $ips])->save();
        if ($roleIps !== null) {
            Role::findByName('editor')->update(['allow_from_remote' => true, 'allowed_ips' => $roleIps]);
        }
        $this->actingAs($user)->withServerVariables(['REMOTE_ADDR' => $ip])->get('/test-ip')->assertStatus($status);
    }
    public function test_ip_form_validation_accepts_cidr_and_rejects_invalid_prefixes(): void
    {
        foreach (['203.0.113.1', '203.0.113.0/24', '2001:db8::/32', '::/0'] as $ip) {
            $this->assertTrue(\Illuminate\Support\Facades\Validator::make(['ip' => $ip], ['ip' => 'ip_or_cidr'])->passes());
        }
        foreach (['invalid', '203.0.113.0/33', '2001:db8::/129', '203.0.113.0/-1'] as $ip) {
            $this->assertFalse(\Illuminate\Support\Facades\Validator::make(['ip' => $ip], ['ip' => 'ip_or_cidr'])->passes());
        }
    }
}

<?php

namespace IlBronza\AccountManager\Tests\Feature;

use App\Models\ProjectSpecific\User;
use IlBronza\AccountManager\Auth\Ldap\DirectoryAuthenticator;
use IlBronza\AccountManager\Auth\Ldap\DirectoryIdentity;
use IlBronza\AccountManager\Auth\Ldap\LdapAccounts;
use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Laravel\Ui\UiServiceProvider;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;

class LdapLoginTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return array_merge(parent::getPackageProviders($app), [UiServiceProvider::class]);
    }

    protected function defineRoutes($router)
    {
        require_once __DIR__ . '/../../src/Http/Controllers/Auth/LoginController.php';
        app('view')->getFinder()->prependLocation(__DIR__ . '/../../resources/views');
        Route::middleware('web')->group(fn () => Auth::routes(['register' => false]));
    }

    protected function identity(string $guid = 'c5171613-1e67-4a79-911b-3ff267ae63a2', string $connection = 'default'): DirectoryIdentity
    {
        return new DirectoryIdentity($connection, $guid, 'Name from AD');
    }

    protected function enableLdap(): void
    {
        config(['accountmanager_ldap.enabled' => true]);
        (require __DIR__ . '/../../database/ldap-migrations/2026_10_27_000000_create_accountmanager_ldap_identities_table.php')->up();
    }

    protected function directory(): \Mockery\MockInterface
    {
        $directory = Mockery::mock(DirectoryAuthenticator::class);
        $this->app->instance(DirectoryAuthenticator::class, $directory);

        return $directory;
    }

    protected function payload(): array
    {
        return ['username' => 'user@ad.example.test', 'password' => 'domain-secret'];
    }

    public function test_disabled_by_default_needs_no_dependency_or_identity_table_and_preserves_local_login(): void
    {
        $this->directory()->shouldNotReceive('authenticate');
        $this->assertFalse(config('accountmanager_ldap.enabled'));
        $this->assertFalse(Schema::hasTable(LdapAccounts::TABLE));
        $this->get('/login/ldap')->assertNotFound();
        $this->postJson('/login/ldap', $this->payload())->assertNotFound();
        $this->get('/login')->assertOk()->assertDontSee(route('accountmanager.ldap.login'));

        $user = $this->makeUserWithRole('editor');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
        $this->assertSame('eloquent', config('auth.providers.users.driver'));
    }

    public function test_enabled_form_and_link_render_without_connecting_to_ad(): void
    {
        config(['accountmanager_ldap.enabled' => true]);
        $this->directory()->shouldNotReceive('authenticate');
        $this->get('/login')->assertOk()->assertSee(route('accountmanager.ldap.login'));
        $this->get('/login/ldap')->assertOk()->assertSee('name="_token"', false)
            ->assertSee('action="' . route('accountmanager.ldap.authenticate') . '"', false)
            ->assertSee('autocomplete="current-password"', false)->assertSee(route('login'));
    }

    public function test_linked_login_preserves_user_data_permissions_and_local_login(): void
    {
        $this->enableLdap();
        $user = $this->makeUserWithRole('editor');
        $before = $user->fresh()->getAttributes();
        app(LdapAccounts::class)->link((string) $user->id, $this->identity());
        $this->directory()->shouldReceive('authenticate')->once()
            ->with('user@ad.example.test', 'domain-secret')->andReturn($this->identity());
        $this->withSession(['url.intended' => '/account-management/users']);
        $oldSessionId = session()->getId();

        $this->post('/login/ldap', $this->payload())->assertRedirect('/account-management/users');
        $this->assertNotSame($oldSessionId, session()->getId());
        $this->assertAuthenticatedAs($user);
        $this->assertSame($before, $user->fresh()->getAttributes());
        $this->assertTrue($user->fresh()->hasRole('editor'));
        $this->assertDatabaseCount('users', 1);
        $this->post('/logout')->assertRedirect('/');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }

    public function test_directory_failure_does_not_fall_back_to_local_password(): void
    {
        $this->enableLdap();
        $user = $this->makeUserWithRole('editor');
        app(LdapAccounts::class)->link((string) $user->id, $this->identity());
        $this->directory()->shouldReceive('authenticate')->once()->andReturnNull();
        $this->postJson('/login/ldap', ['username' => $user->email, 'password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors('username');
        $this->assertGuest();
    }

    public function test_unlinked_directory_user_cannot_provision_or_claim_existing_user(): void
    {
        $this->enableLdap();
        $user = $this->makeUserWithRole('editor', 'user@ad.example.test');
        $this->directory()->shouldReceive('authenticate')->once()->andReturn($this->identity());
        $this->from('/login/ldap')->post('/login/ldap', $this->payload())
            ->assertRedirect('/login/ldap')->assertSessionHasErrors('username')
            ->assertSessionMissing('_old_input.password');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount(LdapAccounts::TABLE, 0);
    }

    public static function deniedLocalUsers(): array
    {
        return ['inactive' => ['active'], 'deleted' => ['deleted_at']];
    }

    #[DataProvider('deniedLocalUsers')]
    public function test_linked_inactive_or_deleted_user_cannot_sign_in(string $column): void
    {
        $this->enableLdap();
        $user = $this->makeUserWithRole('editor');
        app(LdapAccounts::class)->link((string) $user->id, $this->identity());
        DB::table('users')->where('id', $user->id)->update([$column => $column === 'active' ? false : now()]);
        $this->directory()->shouldReceive('authenticate')->once()->andReturn($this->identity());
        $this->postJson('/login/ldap', $this->payload())->assertUnprocessable();
        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_outage_is_contained_and_does_not_expose_or_flash_credentials(): void
    {
        $this->enableLdap();
        $this->directory()->shouldReceive('authenticate')->twice()->andThrow(new \RuntimeException('sensitive-directory-diagnostics'));
        Log::shouldReceive('warning')->twice()->with('LDAP sign-in unavailable.', ['exception_type' => \RuntimeException::class]);
        $this->postJson('/login/ldap', $this->payload())->assertStatus(503)->assertDontSee('sensitive-directory-diagnostics');
        $this->from('/login/ldap')->post('/login/ldap', $this->payload())->assertRedirect('/login/ldap')
            ->assertSessionHasErrors('username')->assertSessionMissing('_old_input.password');
        $this->get('/login/ldap')->assertOk()->assertDontSee('domain-secret')->assertDontSee('sensitive-directory-diagnostics');
        $this->assertGuest();
    }

    public function test_validation_csrf_and_throttle_run_before_directory_authentication(): void
    {
        $this->enableLdap();
        $directory = $this->directory();
        $this->postJson('/login/ldap', ['username' => ['bad'], 'password' => ''])->assertUnprocessable()
            ->assertJsonValidationErrors(['username', 'password']);
        $this->app->instance('env', 'production');
        $this->postJson('/login/ldap', $this->payload())->assertStatus(419);
        $this->app->instance('env', 'testing');
        $directory->shouldReceive('authenticate')->times(5)->andReturnNull();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/login/ldap', $this->payload())->assertUnprocessable();
        }
        $this->postJson('/login/ldap', array_replace($this->payload(), ['username' => 'USER@AD.EXAMPLE.TEST']))->assertStatus(429);
        $this->assertGuest();
    }

    public function test_existing_session_is_not_replaced_by_another_directory_identity(): void
    {
        $this->enableLdap();
        $user = $this->makeUserWithRole('editor');
        $this->actingAs($user);
        $this->directory()->shouldNotReceive('authenticate');
        $this->get('/login/ldap')->assertRedirect('/home');
        $this->post('/login/ldap', $this->payload())->assertRedirect('/home');
        $this->assertAuthenticatedAs($user);
    }

    public function test_cli_link_is_idempotent_rejects_conflicts_and_unlinks_only_its_directory(): void
    {
        $this->enableLdap();
        $first = $this->makeUserWithRole('editor');
        $second = $this->makeUserWithRole('editor', 'second@example.test');
        $this->directory()->shouldReceive('find')->times(3)->with('ad-user')->andReturn($this->identity());
        foreach ([[$first, 0], [$first, 0], [$second, 1]] as [$user, $exit]) {
            $this->artisan('accountmanager:ldap-link', ['user' => $user->id, 'username' => 'ad-user'])->assertExitCode($exit);
        }
        app(LdapAccounts::class)->link((string) $first->id, $this->identity(connection: 'another-directory'));
        $this->assertDatabaseCount(LdapAccounts::TABLE, 2);
        $this->artisan('accountmanager:ldap-link', ['user' => $first->id, '--unlink' => true])->assertExitCode(0);
        $this->assertDatabaseCount(LdapAccounts::TABLE, 1);
        $this->assertNull(app(LdapAccounts::class)->resolve($this->identity()));
        $this->assertTrue(app(LdapAccounts::class)->resolve($this->identity(connection: 'another-directory'))->is($first));
    }

    public function test_local_user_cannot_be_silently_relinked_to_a_different_guid(): void
    {
        $this->enableLdap();
        $user = $this->makeUserWithRole('editor');
        app(LdapAccounts::class)->link((string) $user->id, $this->identity());
        $this->expectException(\DomainException::class);
        app(LdapAccounts::class)->link((string) $user->id, $this->identity('1286631a-818c-47bc-9e91-ad6fc4d0e090'));
    }

    public function test_sync_name_is_explicit_and_never_changes_password_or_roles(): void
    {
        $this->enableLdap();
        config(['accountmanager_ldap.sync_name' => true]);
        $user = $this->makeUserWithRole('editor');
        $hash = $user->password;
        app(LdapAccounts::class)->link((string) $user->id, $this->identity());
        app(LdapAccounts::class)->resolve($this->identity());
        $user->refresh();
        $this->assertSame('Name from AD', $user->name);
        $this->assertSame($hash, $user->password);
        $this->assertTrue($user->hasRole('editor'));
    }

    public function test_optional_migration_rolls_back_without_changing_users_or_roles(): void
    {
        $user = $this->makeUserWithRole('editor');
        $before = $user->fresh()->getAttributes();
        $migration = require __DIR__ . '/../../database/ldap-migrations/2026_10_27_000000_create_accountmanager_ldap_identities_table.php';
        $migration->up();
        app(LdapAccounts::class)->link((string) $user->id, $this->identity());
        $migration->down();
        $this->assertFalse(Schema::hasTable(LdapAccounts::TABLE));
        $this->assertSame($before, $user->fresh()->getAttributes());
        $this->assertTrue($user->fresh()->hasRole('editor'));
        $migration->up();
        $this->assertDatabaseCount(LdapAccounts::TABLE, 0);
    }
}

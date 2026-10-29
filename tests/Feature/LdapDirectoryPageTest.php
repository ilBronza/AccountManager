<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Auth\Ldap\DirectoryIdentity;
use IlBronza\AccountManager\Auth\Ldap\DirectoryReader;
use IlBronza\AccountManager\Auth\Ldap\DirectoryUser;
use IlBronza\AccountManager\Auth\Ldap\LdapAccounts;
use IlBronza\AccountManager\Auth\Ldap\LdapDirectoryUsers;
use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;

class LdapDirectoryPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['accountmanager_ldap.directory_enabled' => true]);
        foreach (glob(__DIR__.'/../../database/ldap-migrations/*.php') as $file) (require $file)->up();
    }

    protected function identity(int $number): DirectoryIdentity
    {
        return new DirectoryIdentity('default', sprintf('c5171613-1e67-4a79-911b-%012d', $number), 'AD Person '.$number);
    }

    protected function import(int $number = 1, ?string $name = null): object
    {
        $identity = $this->identity($number);
        $record = new DirectoryUser(new DirectoryIdentity('default', $identity->guid, $name ?? $identity->name),
            'ad'.$number, 'ad'.$number.'@example.test', 'ad'.$number.'@example.test',
            'cn=ad'.$number.',dc=example,dc=test', true, true);
        $reader = Mockery::mock(DirectoryReader::class);
        $reader->shouldReceive('users')->once()->andReturn([$record]);
        // This helper preserves previous fixture entries across sequential imports.
        app(LdapDirectoryUsers::class)->import($reader);
        DB::table(LdapDirectoryUsers::TABLE)->update(['missing' => false]);

        return app(LdapDirectoryUsers::class)->query()->where('guid', $identity->guid)->first();
    }

    protected function admin(): void
    {
        $this->actingAs($this->makeUserWithRole('superadmin', 'admin@example.test'));
    }

    protected function url(?object $entry = null): string
    {
        return '/account-management/ldap-users'.($entry ? '/'.$entry->id.'/link' : '');
    }

    public function test_directory_and_association_actions_require_authentication_and_management_role(): void
    {
        $entry = $this->import();
        $this->getJson($this->url())->assertUnauthorized();
        $this->postJson($this->url($entry), ['user_id' => '1'])->assertUnauthorized();
        $this->deleteJson($this->url($entry), ['user_id' => '1'])->assertUnauthorized();
        $this->actingAs($this->makeUserWithRole('editor'));
        $this->getJson($this->url())->assertForbidden();
        $this->postJson($this->url($entry), ['user_id' => '1'])->assertForbidden();
        $this->deleteJson($this->url($entry), ['user_id' => '1'])->assertForbidden();
        $this->assertDatabaseCount(LdapAccounts::TABLE, 0);
    }

    public function test_inventory_is_separate_from_enabling_login_and_defaults_to_unlinked_entries(): void
    {
        $this->admin();
        $first = $this->import();
        $second = $this->import(2);
        $local = $this->makeUserWithRole('worker', 'ad1@example.test');
        app(LdapAccounts::class)->link((string) $local->id, $this->identity(2));

        $this->get($this->url())->assertOk()->assertSee('AD Person 1')->assertDontSee('AD Person 2')
            ->assertSee(__('accountmanager::directory.login_disabled'))
            ->assertSee('worker')->assertSee('name="_token"', false);
        $this->get($this->url().'?status=linked')->assertOk()->assertSee('AD Person 2')->assertDontSee('AD Person 1')
            ->assertSee($local->email)->assertSee('worker');
        $this->get($this->url().'?status=all&q=ad2')->assertOk()->assertSee('AD Person 2')->assertDontSee('AD Person 1');
        $this->assertFalse(config('accountmanager_ldap.enabled'));
        $this->assertDatabaseCount(LdapAccounts::TABLE, 1);
    }

    public function test_manual_association_preserves_the_same_user_id_credentials_roles_and_current_session(): void
    {
        $this->admin();
        $actor = auth()->id();
        $entry = $this->import();
        $user = $this->makeUserWithRole('worker');
        $before = $user->fresh()->getAttributes();
        $this->app->instance('env', 'production');
        $this->postJson($this->url($entry), ['user_id' => (string) $user->id])->assertStatus(419);
        $this->app->instance('env', 'testing');
        $this->post($this->url($entry), ['user_id' => (string) $user->id, 'roles' => ['superadmin'], 'password' => 'ignored'])
            ->assertRedirect($this->url());
        $this->assertTrue(app(LdapAccounts::class)->resolve($this->identity(1))->is($user));
        $this->assertSame($before, $user->fresh()->getAttributes());
        $this->assertSame(['worker'], $user->fresh()->getRoleNames()->all());
        $this->assertSame($actor, auth()->id());
        $this->assertFalse(config('accountmanager_ldap.enabled'));
    }

    public function test_conflicting_associations_cannot_silently_merge_or_relink_users(): void
    {
        $this->admin();
        $first = $this->import();
        $second = $this->import(2);
        $user = $this->makeUserWithRole('worker');
        $other = $this->makeUserWithRole('worker', 'other@example.test');
        app(LdapAccounts::class)->link((string) $user->id, $this->identity(1));

        $this->postJson($this->url($second), ['user_id' => (string) $user->id])->assertUnprocessable();
        $this->postJson($this->url($first), ['user_id' => (string) $other->id])->assertUnprocessable();
        $this->assertDatabaseCount(LdapAccounts::TABLE, 1);
        $this->assertTrue(app(LdapAccounts::class)->resolve($this->identity(1))->is($user));
    }

    public function test_missing_inactive_deleted_and_nonexistent_targets_are_rejected(): void
    {
        $this->admin();
        $entry = $this->import();
        $user = $this->makeUserWithRole('worker');
        $this->postJson($this->url($entry), ['user_id' => '999999'])->assertUnprocessable();
        DB::table('users')->where('id', $user->id)->update(['active' => false]);
        $this->postJson($this->url($entry), ['user_id' => (string) $user->id])->assertUnprocessable();
        DB::table('users')->where('id', $user->id)->update(['active' => true, 'deleted_at' => now()]);
        $this->postJson($this->url($entry), ['user_id' => (string) $user->id])->assertUnprocessable();
        DB::table('users')->where('id', $user->id)->update(['deleted_at' => null]);
        DB::table(LdapDirectoryUsers::TABLE)->where('id', $entry->id)->update(['missing' => true]);
        $this->postJson($this->url($entry), ['user_id' => (string) $user->id])->assertUnprocessable();
        $this->get($this->url())->assertOk()->assertDontSee('action="'.url($this->url($entry)).'"', false);
        $this->assertDatabaseCount(LdapAccounts::TABLE, 0);
    }

    public function test_unlink_requires_the_current_association_and_preserves_local_data(): void
    {
        $this->admin();
        $entry = $this->import();
        $user = $this->makeUserWithRole('worker');
        $before = $user->fresh()->getAttributes();
        app(LdapAccounts::class)->link((string) $user->id, $this->identity(1));
        app(LdapAccounts::class)->link((string) $user->id,
            new DirectoryIdentity('another', $this->identity(1)->guid, 'Another directory'));
        $this->deleteJson($this->url($entry), ['user_id' => '999999'])->assertUnprocessable();
        $this->assertDatabaseCount(LdapAccounts::TABLE, 2);
        $this->delete($this->url($entry), ['user_id' => (string) $user->id])->assertRedirect($this->url());
        $this->assertDatabaseCount(LdapAccounts::TABLE, 1);
        $this->assertDatabaseHas(LdapAccounts::TABLE, ['connection' => 'another']);
        $this->assertSame($before, $user->fresh()->getAttributes());
        $this->assertSame(['worker'], $user->fresh()->getRoleNames()->all());
    }

    public function test_configurable_managers_cannot_associate_privileged_local_accounts(): void
    {
        $firstUser = $this->makeUserWithRole('worker', 'first@example.test');
        $manager = $this->makeUserWithRole('accountManagerUsers', 'manager@example.test');
        $root = $this->makeUserWithRole('superadmin', 'root@example.test');
        config(['accountmanager_ldap.management_roles' => ['superadmin', 'accountManagerUsers']]);
        $this->actingAs($manager);
        $entry = $this->import();
        $this->postJson($this->url($entry), ['user_id' => (string) $firstUser->id])->assertForbidden();
        $this->postJson($this->url($entry), ['user_id' => (string) $root->id])->assertForbidden();
        $this->get($this->url())->assertOk()->assertDontSee('<option value="'.$firstUser->id.'"', false)
            ->assertDontSee('<option value="'.$root->id.'"', false);
        $this->assertDatabaseCount(LdapAccounts::TABLE, 0);
    }

    public function test_other_directory_records_are_neither_visible_nor_mutable(): void
    {
        $this->admin();
        $entry = $this->import();
        DB::table(LdapDirectoryUsers::TABLE)->where('id', $entry->id)->update(['connection' => 'another']);
        $this->get($this->url())->assertOk()->assertDontSee('AD Person 1');
        $this->postJson($this->url($entry), ['user_id' => '1'])->assertNotFound();
        $this->deleteJson($this->url($entry), ['user_id' => '1'])->assertNotFound();
    }

    public function test_directory_values_are_escaped_and_disabled_accounts_remain_visible(): void
    {
        $this->admin();
        $entry = $this->import(name: '<script>alert(1)</script>');
        DB::table(LdapDirectoryUsers::TABLE)->where('id', $entry->id)->update(['enabled' => false, 'in_required_group' => false]);
        config(['accountmanager_ldap.required_group_dn' => 'cn=allowed,dc=example,dc=test']);
        $this->get($this->url())->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)->assertSee(__('accountmanager::directory.disabled'))
            ->assertSee(__('accountmanager::directory.outside_group'));
    }

    public function test_feature_flag_and_optional_migration_have_independent_failure_states(): void
    {
        $this->admin();
        config(['accountmanager_ldap.directory_enabled' => false]);
        $this->get($this->url())->assertNotFound();
        config(['accountmanager_ldap.directory_enabled' => true]);
        Schema::drop(LdapDirectoryUsers::TABLE);
        $this->get($this->url())->assertOk()->assertSee(__('accountmanager::directory.migration_required'));
        $this->postJson($this->url((object) ['id' => 1]), ['user_id' => '1'])->assertStatus(409);
        $this->assertDatabaseCount('users', 1);
    }
}

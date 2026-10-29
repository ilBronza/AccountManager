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

class LdapDirectoryImportPageTest extends TestCase
{
    private const URL = '/account-management/ldap-users';

    protected function setUp(): void
    {
        parent::setUp();
        config(['accountmanager_ldap.directory_enabled' => true]);
        foreach (glob(__DIR__.'/../../database/ldap-migrations/*.php') as $file) (require $file)->up();
    }

    private function admin(): void
    {
        $this->actingAs($this->makeUserWithRole('superadmin', 'admin@example.test'));
    }

    private function entry(int $id, string $name = 'AD person', bool $enabled = true, bool $inGroup = true): DirectoryUser
    {
        return new DirectoryUser(new DirectoryIdentity('default', sprintf('c5171613-1e67-4a79-911b-%012d', $id), $name),
            'ad'.$id, 'ad'.$id.'@example.test', null, 'cn=ad'.$id.',dc=example,dc=test', $enabled, $inGroup);
    }

    private function reader(iterable $entries, int $times = 1): DirectoryReader
    {
        $reader = Mockery::mock(DirectoryReader::class);
        $reader->shouldReceive('users')->times($times)->andReturn($entries);
        $this->app->instance(DirectoryReader::class, $reader);

        return $reader;
    }

    private function protectedData(): array
    {
        $data = [];
        foreach (['users', 'users__userdata', 'roles', 'permissions', 'model_has_roles',
            'model_has_permissions', 'role_has_permissions', LdapAccounts::TABLE] as $table) {
            $data[$table] = DB::table($table)->get()->toJson();
        }

        return $data;
    }

    public function test_import_actions_require_authentication_management_role_and_enabled_directory(): void
    {
        $reader = Mockery::mock(DirectoryReader::class);
        $reader->shouldNotReceive('users');
        $this->app->instance(DirectoryReader::class, $reader);
        $this->postJson(self::URL.'/import', ['mode' => 'import'])->assertUnauthorized();
        $this->actingAs($this->makeUserWithRole('editor'));
        $this->postJson(self::URL.'/import', ['mode' => 'preview'])->assertForbidden();
        $this->postJson(self::URL.'/import', ['mode' => 'import'])->assertForbidden();
        $this->admin();
        config(['accountmanager_ldap.directory_enabled' => false]);
        $this->postJson(self::URL.'/import', ['mode' => 'import'])->assertNotFound();
        $this->assertDatabaseCount(LdapDirectoryUsers::TABLE, 0);
    }

    public function test_page_shows_import_buttons_and_the_current_login_state(): void
    {
        $this->admin();
        $this->get(self::URL)->assertOk()->assertSee(__('accountmanager::directory.preview'))
            ->assertSee(__('accountmanager::directory.import'))->assertSee('name="mode" value="preview"', false)
            ->assertSee('name="mode" value="import"', false)->assertSee('name="_token"', false)
            ->assertSee(__('accountmanager::directory.login_disabled'));
        config(['accountmanager_ldap.enabled' => true]);
        $this->get(self::URL)->assertOk()->assertSee(__('accountmanager::directory.login_enabled'))
            ->assertDontSee(__('accountmanager::directory.login_disabled'));
    }

    public function test_preview_reads_the_full_directory_and_preserves_all_saved_data(): void
    {
        $this->admin();
        $inventory = app(LdapDirectoryUsers::class);
        $inventory->import($this->reader([$this->entry(1)]));
        $before = $this->protectedData();
        $beforeInventory = $inventory->query()->get()->toJson();
        $this->reader([$this->entry(2), $this->entry(3, enabled: false), $this->entry(4, inGroup: false)]);

        $this->post(self::URL.'/import', ['mode' => 'preview'])->assertRedirect(self::URL)
            ->assertSessionHas('ldap_import_result', ['preview' => true, 'stats' => ['total' => 3, 'active' => 2, 'eligible' => 1]]);
        $this->get(self::URL)->assertOk()->assertSee(__('accountmanager::directory.preview_success'))
            ->assertSee(__('accountmanager::directory.import_total'));
        $this->assertSame($before, $this->protectedData());
        $this->assertSame($beforeInventory, $inventory->query()->get()->toJson());
        $this->assertFalse(config('accountmanager_ldap.enabled'));
    }

    public function test_import_refreshes_all_ad_states_and_preserves_local_users_roles_links_and_session(): void
    {
        $this->admin();
        $actor = auth()->id();
        $local = $this->makeUserWithRole('worker', 'local@example.test');
        $inventory = app(LdapDirectoryUsers::class);
        $first = $this->entry(1);
        $inventory->import($this->reader([$first, $this->entry(4)]));
        app(LdapAccounts::class)->link((string) $local->id, $first->identity);
        $id = $inventory->query()->where('guid', $first->identity->guid)->value('id');
        $before = $this->protectedData();
        config(['accountmanager_ldap.enabled' => true]);
        $this->reader([$this->entry(1, 'Renamed AD person'), $this->entry(2, enabled: false), $this->entry(3, inGroup: false)]);

        $this->post(self::URL.'/import', ['mode' => 'import'])->assertRedirect(self::URL)
            ->assertSessionHas('ldap_import_result', ['preview' => false, 'stats' => [
                'total' => 3, 'active' => 2, 'eligible' => 1, 'linked' => 1, 'unlinked' => 2, 'missing' => 1,
            ]]);
        $this->get(self::URL.'?status=linked')->assertOk()->assertSee(__('accountmanager::directory.import_success'));
        $rows = $this->get(self::URL.'?status=linked', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data');
        $html = collect($rows)->flatten()->implode(' ');
        $this->assertStringContainsString('Renamed AD person', $html);
        $this->assertStringContainsString('worker', $html);
        $this->assertStringContainsString($local->email, $html);
        $this->assertSame($before, $this->protectedData());
        $this->assertSame($actor, auth()->id());
        $this->assertTrue(config('accountmanager_ldap.enabled'));
        $this->assertDatabaseHas(LdapDirectoryUsers::TABLE, ['id' => $id, 'name' => 'Renamed AD person']);
        $this->assertDatabaseHas(LdapDirectoryUsers::TABLE, ['samaccountname' => 'ad2', 'enabled' => false]);
        $this->assertDatabaseHas(LdapDirectoryUsers::TABLE, ['samaccountname' => 'ad3', 'in_required_group' => false]);
        $this->assertDatabaseHas(LdapDirectoryUsers::TABLE, ['samaccountname' => 'ad4', 'missing' => true]);
    }

    public function test_failed_read_keeps_the_last_snapshot_and_hides_exception_details(): void
    {
        $this->admin();
        $inventory = app(LdapDirectoryUsers::class);
        $inventory->import($this->reader([$this->entry(1)]));
        $before = $inventory->query()->get()->toJson();
        $this->reader((function () {
            yield $this->entry(2);
            throw new \RuntimeException('private-bind-password-and-dn');
        })());

        $this->post(self::URL.'/import', ['mode' => 'import'])->assertRedirect(self::URL)
            ->assertSessionHasErrors(['ldap_import' => __('accountmanager::directory.import_failed')])
            ->assertSessionMissing('ldap_import_result');
        $this->get(self::URL)->assertOk()->assertSee(__('accountmanager::directory.import_failed'))
            ->assertDontSee('private-bind-password-and-dn');
        $this->assertSame($before, $inventory->query()->get()->toJson());
    }

    public function test_invalid_modes_are_rejected_before_reading_the_directory(): void
    {
        $this->admin();
        $reader = Mockery::mock(DirectoryReader::class);
        $reader->shouldNotReceive('users');
        $this->app->instance(DirectoryReader::class, $reader);
        foreach ([[], ['mode' => 'unexpected'], ['mode' => ['import']]] as $input) {
            $this->postJson(self::URL.'/import', $input)->assertUnprocessable()->assertJsonValidationErrors('mode');
        }
        $this->assertDatabaseCount(LdapDirectoryUsers::TABLE, 0);
    }

    public function test_both_actions_require_csrf(): void
    {
        $this->admin();
        $reader = Mockery::mock(DirectoryReader::class);
        $reader->shouldNotReceive('users');
        $this->app->instance(DirectoryReader::class, $reader);
        $this->app->instance('env', 'production');
        $this->postJson(self::URL.'/import', ['mode' => 'preview'])->assertStatus(419);
        $this->postJson(self::URL.'/import', ['mode' => 'import'])->assertStatus(419);
        $this->app->instance('env', 'testing');
        $this->assertDatabaseCount(LdapDirectoryUsers::TABLE, 0);
    }

    public function test_repeated_requests_are_throttled_per_manager(): void
    {
        $this->admin();
        $this->reader([$this->entry(1)], 4);
        for ($i = 0; $i < 3; $i++) {
            $this->post(self::URL.'/import', ['mode' => 'preview'])->assertRedirect(self::URL);
        }
        $this->postJson(self::URL.'/import', ['mode' => 'import'])->assertStatus(429);
        $this->actingAs($this->makeUserWithRole('superadmin', 'another-admin@example.test'));
        $this->post(self::URL.'/import', ['mode' => 'preview'])->assertRedirect(self::URL);
        $this->assertDatabaseCount(LdapDirectoryUsers::TABLE, 0);
    }

    public function test_preview_is_available_without_migrations_but_import_is_rejected(): void
    {
        $this->admin();
        Schema::drop(LdapDirectoryUsers::TABLE);
        Schema::drop(LdapAccounts::TABLE);
        $this->reader([$this->entry(1)]);
        $this->get(self::URL)->assertOk()->assertSee('name="mode" value="import"  disabled', false);
        $this->post(self::URL.'/import', ['mode' => 'preview'])->assertRedirect(self::URL)
            ->assertSessionHas('ldap_import_result.stats.total', 1);
        $this->postJson(self::URL.'/import', ['mode' => 'import'])->assertStatus(409);
        $this->assertFalse(Schema::hasTable(LdapDirectoryUsers::TABLE));
        $this->assertFalse(Schema::hasTable(LdapAccounts::TABLE));
    }
}

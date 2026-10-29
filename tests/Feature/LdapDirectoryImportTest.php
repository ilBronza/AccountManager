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

class LdapDirectoryImportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (glob(__DIR__.'/../../database/ldap-migrations/*.php') as $file) (require $file)->up();
    }

    protected function entry(int $number, string $name = 'Directory user', bool $enabled = true, bool $inGroup = true, string $connection = 'default'): DirectoryUser
    {
        return new DirectoryUser(new DirectoryIdentity($connection, sprintf('c5171613-1e67-4a79-911b-%012d', $number), $name),
            'user'.$number, 'user'.$number.'@example.test', 'user'.$number.'@example.test',
            'cn=user'.$number.',dc=example,dc=test', $enabled, $inGroup);
    }

    protected function reader(iterable $entries): DirectoryReader
    {
        $reader = Mockery::mock(DirectoryReader::class);
        $reader->shouldReceive('users')->once()->andReturn($entries);

        return $reader;
    }

    public function test_import_includes_disabled_and_outside_group_accounts_without_creating_users_or_roles(): void
    {
        $local = $this->makeUserWithRole('worker');
        $before = $local->fresh()->getAttributes();
        $stats = app(LdapDirectoryUsers::class)->import($this->reader([
            $this->entry(1), $this->entry(2, enabled: false), $this->entry(3, inGroup: false),
        ]));

        $this->assertSame(['total' => 3, 'active' => 2, 'eligible' => 1, 'linked' => 0, 'unlinked' => 3, 'missing' => 0], $stats);
        $this->assertDatabaseCount(LdapDirectoryUsers::TABLE, 3);
        $this->assertDatabaseCount(LdapAccounts::TABLE, 0);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($before, $local->fresh()->getAttributes());
        $this->assertSame(['worker'], $local->fresh()->getRoleNames()->all());
        $this->assertFalse(config('accountmanager_ldap.enabled'));
    }

    public function test_reimport_updates_by_guid_and_preserves_explicit_local_associations(): void
    {
        $inventory = app(LdapDirectoryUsers::class);
        $first = $this->entry(1);
        $inventory->import($this->reader([$first, $this->entry(2)]));
        $local = $this->makeUserWithRole('worker');
        app(LdapAccounts::class)->link((string) $local->id, $first->identity);
        $id = $inventory->query()->where('guid', $first->identity->guid)->value('id');
        $renamed = new DirectoryUser(new DirectoryIdentity('default', $first->identity->guid, 'New name'),
            'renamed', 'renamed@example.test', null, 'cn=renamed,dc=example,dc=test', true, true);
        $stats = $inventory->import($this->reader([$renamed]));

        $this->assertSame(1, $stats['linked']);
        $this->assertSame(1, $stats['missing']);
        $this->assertSame($id, $inventory->query()->where('guid', $first->identity->guid)->value('id'));
        $this->assertDatabaseHas(LdapDirectoryUsers::TABLE, ['id' => $id, 'samaccountname' => 'renamed', 'name' => 'New name']);
        $this->assertTrue(app(LdapAccounts::class)->resolve($first->identity)->is($local));
        $this->assertSame('Test User', $local->fresh()->name);
        $inventory->import($this->reader([$renamed, $this->entry(2)]));
        $this->assertDatabaseCount(LdapDirectoryUsers::TABLE, 2);
        $this->assertSame(0, $inventory->query()->where('missing', true)->count());
    }

    public function test_failed_directory_read_does_not_apply_a_partial_snapshot_or_mark_users_missing(): void
    {
        $inventory = app(LdapDirectoryUsers::class);
        $inventory->import($this->reader([$this->entry(1)]));
        $before = DB::table(LdapDirectoryUsers::TABLE)->get()->toJson();
        $partial = (function () {
            yield $this->entry(2);
            throw new \RuntimeException('Connection interrupted');
        })();
        try {
            $inventory->import($this->reader($partial));
            $this->fail('A partial directory read must fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Connection interrupted', $exception->getMessage());
        }
        $this->assertSame($before, DB::table(LdapDirectoryUsers::TABLE)->get()->toJson());
    }

    public function test_duplicate_and_foreign_directory_identities_are_rejected_before_writing(): void
    {
        foreach ([[$this->entry(1), $this->entry(1)], [$this->entry(1, connection: 'another')]] as $entries) {
            try {
                app(LdapDirectoryUsers::class)->import($this->reader($entries));
                $this->fail('Invalid import must fail.');
            } catch (\RuntimeException $exception) {
                $this->assertStringContainsString('Invalid or duplicate', $exception->getMessage());
            }
        }
        $this->assertDatabaseCount(LdapDirectoryUsers::TABLE, 0);
    }

    public function test_import_refresh_is_scoped_to_its_connection_and_never_removes_links(): void
    {
        $inventory = app(LdapDirectoryUsers::class);
        $inventory->import($this->reader([$this->entry(1)]));
        $user = $this->makeUserWithRole('worker');
        app(LdapAccounts::class)->link((string) $user->id, $this->entry(1)->identity);
        config(['accountmanager_ldap.connection' => 'another']);
        $inventory->import($this->reader([$this->entry(2, connection: 'another')]));
        config(['accountmanager_ldap.connection' => 'default']);
        $inventory->import($this->reader([]));

        $this->assertDatabaseHas(LdapDirectoryUsers::TABLE, ['connection' => 'another', 'missing' => false]);
        $this->assertDatabaseHas(LdapDirectoryUsers::TABLE, ['connection' => 'default', 'missing' => true]);
        $this->assertDatabaseCount(LdapAccounts::TABLE, 1);
    }

    public function test_preview_requires_no_inventory_migrations_and_never_creates_them(): void
    {
        Schema::drop(LdapDirectoryUsers::TABLE);
        Schema::drop(LdapAccounts::TABLE);
        $this->app->instance(DirectoryReader::class, $this->reader([$this->entry(1)]));

        $this->artisan('accountmanager:ldap-import', ['--dry-run' => true])
            ->expectsOutput('Directory preview complete. No changes saved.')->assertExitCode(0);
        $this->assertFalse(Schema::hasTable(LdapDirectoryUsers::TABLE));
        $this->assertFalse(Schema::hasTable(LdapAccounts::TABLE));
        $this->assertDatabaseCount('users', 0);
    }
}

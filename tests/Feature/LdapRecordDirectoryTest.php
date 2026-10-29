<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Auth\Ldap\LdapAccounts;
use IlBronza\AccountManager\Auth\Ldap\LdapRecordDirectory;
use IlBronza\AccountManager\Tests\TestCase;
use LdapRecord\Container;
use LdapRecord\Laravel\LdapServiceProvider;
use LdapRecord\Laravel\Testing\DirectoryEmulator;
use LdapRecord\Models\ActiveDirectory\Group;
use LdapRecord\Models\ActiveDirectory\User;
use LdapRecord\Testing\LdapFake;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;

class LdapRecordDirectoryTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return array_merge(parent::getPackageProviders($app), [LdapServiceProvider::class]);
    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);
        $app['config']->set('accountmanager_ldap.enabled', true);
        $app['config']->set('ldap.default', 'default');
        $app['config']->set('ldap.connections.default', [
            'hosts' => ['dc.example.test'], 'base_dn' => 'dc=example,dc=test',
            'username' => 'reader@example.test', 'password' => 'service-secret',
            'use_ssl' => true, 'use_tls' => false, 'port' => 636,
            'options' => [LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_DEMAND],
        ]);
    }

    protected function setUp(): void
    {
        if (! class_exists(DirectoryEmulator::class) || ! extension_loaded('ldap')) {
            $this->markTestSkipped('Install the optional LDAP dev dependency and ext-ldap to run directory emulator tests.');
        }
        parent::setUp();
        DirectoryEmulator::setup('default');
    }

    protected function tearDown(): void
    {
        if (isset($this->app) && $this->app && class_exists(DirectoryEmulator::class)) {
            DirectoryEmulator::tearDown();
            Container::getInstance()->flush();
        }
        parent::tearDown();
    }

    protected function user(array $attributes = []): User
    {
        return User::create(array_replace([
            'cn' => 'Mario Rossi', 'displayname' => 'Mario Rossi AD',
            'userprincipalname' => 'mario@example.test', 'samaccountname' => 'mario',
            'mail' => 'mario@example.test', 'useraccountcontrol' => 512,
        ], $attributes))->fresh();
    }

    protected function allowPassword(User $user, string $password = 'correct-domain-password'): void
    {
        Container::getConnection()->getLdapConnection()->expect(
            LdapFake::operation('bind')->with($user->getDn(), $password)->andReturnResponse()
        );
    }

    public function test_real_adapter_checks_password_and_preserves_guid_after_username_change(): void
    {
        $user = $this->user();
        $this->allowPassword($user);
        $directory = new LdapRecordDirectory;
        $identity = $directory->authenticate('mario@example.test', 'correct-domain-password');
        $this->assertSame($user->getConvertedGuid(), $identity->guid);
        $this->assertSame('Mario Rossi AD', $identity->name);
        $this->assertNull($directory->authenticate('mario@example.test', 'incorrect-password'));
        $this->assertNull($directory->authenticate('mario@example.test', ''));
        $user->userprincipalname = 'renamed@example.test';
        $user->save();
        $this->assertNull($directory->find('mario@example.test'));
        $this->assertSame($identity->guid, $directory->find('renamed@example.test')->guid);
    }

    public function test_real_adapter_authenticates_through_http_with_existing_local_roles(): void
    {
        (require __DIR__ . '/../../database/ldap-migrations/2026_10_27_000000_create_accountmanager_ldap_identities_table.php')->up();
        $ldap = $this->user();
        $this->allowPassword($ldap);
        $local = $this->makeUserWithRole('editor');
        app(LdapAccounts::class)->link((string) $local->id, (new LdapRecordDirectory)->find('mario@example.test'));
        $this->post('/login/ldap', ['username' => 'mario@example.test', 'password' => 'correct-domain-password'])->assertRedirect('/home');
        $this->assertAuthenticatedAs($local);
        $this->assertTrue($local->fresh()->hasRole('editor'));
    }

    public function test_disabled_account_is_rejected_even_if_bind_would_succeed(): void
    {
        $user = $this->user(['useraccountcontrol' => 514]);
        $this->allowPassword($user);
        $this->assertNull((new LdapRecordDirectory)->authenticate('mario@example.test', 'correct-domain-password'));
        $this->assertNull((new LdapRecordDirectory)->find('mario@example.test'));
    }

    public function test_ambiguous_mail_and_filter_metacharacters_cannot_select_another_user(): void
    {
        $this->user();
        $this->user(['cn' => 'Another User', 'userprincipalname' => 'another@example.test']);
        config(['accountmanager_ldap.login_attribute' => 'mail']);
        $this->assertNull((new LdapRecordDirectory)->find('mario@example.test'));
        config(['accountmanager_ldap.login_attribute' => 'samaccountname']);
        $this->assertNull((new LdapRecordDirectory)->find('*)(|(samaccountname=*))'));
        $this->assertNull((new LdapRecordDirectory)->find(''));
    }

    public function test_required_group_accepts_nested_members_and_denies_nonmembers(): void
    {
        $user = $this->user();
        $group = Group::create(['cn' => 'Sael Users']);
        $child = Group::create(['cn' => 'Sael Editors']);
        config(['accountmanager_ldap.required_group_dn' => $group->getDn()]);
        $directory = new LdapRecordDirectory;
        $this->assertNull($directory->find('mario@example.test'));
        $group->members()->attach($child);
        $child->members()->attach($user);
        $this->assertSame($user->getConvertedGuid(), $directory->find('mario@example.test')->guid);
        config(['accountmanager_ldap.required_group_dn' => 'cn=Missing,dc=example,dc=test']);
        $this->assertNull($directory->find('mario@example.test'));
    }

    public static function unsafeConnections(): array
    {
        return [
            'ssl and starttls together' => ['use_tls', true],
            'anonymous service bind' => ['username', ''],
            'empty service password' => ['password', ''],
        ];
    }

    #[DataProvider('unsafeConnections')]
    public function test_invalid_connection_configuration_fails_closed(string $key, $value): void
    {
        Container::getConnection()->getConfiguration()->set($key, $value);
        $this->expectException(\RuntimeException::class);
        (new LdapRecordDirectory)->find('mario@example.test');
    }

    public function test_disabled_certificate_verification_is_rejected(): void
    {
        Container::getConnection()->getConfiguration()->set('options', [LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER]);
        $this->expectException(\RuntimeException::class);
        (new LdapRecordDirectory)->find('mario@example.test');
    }

    public function test_plain_ldap_diagnostic_uses_samaccountname_without_enabling_login_or_writing_local_users(): void
    {
        $connection = Container::getConnection()->getConfiguration();
        $connection->set('use_ssl', false);
        $connection->set('use_tls', false);
        $connection->set('port', 389);
        config(['accountmanager_ldap.enabled' => false, 'accountmanager_ldap.login_attribute' => 'samaccountname']);
        $user = $this->user();
        $this->allowPassword($user);

        $this->artisan('accountmanager:ldap-test', ['username' => 'mario'])
            ->expectsOutput('AD user verified. No local users, associations or sessions changed.')
            ->assertExitCode(0);
        $this->artisan('accountmanager:ldap-test', ['username' => 'mario', '--authenticate' => true])
            ->expectsQuestion('AD password (input hidden)', 'correct-domain-password')
            ->expectsOutput('AD password verified.')
            ->assertExitCode(0);
        $this->assertNull((new LdapRecordDirectory)->authenticate('mario', 'incorrect-password'));
        $this->assertFalse(config('accountmanager_ldap.enabled'));
        $this->assertFalse(Schema::hasTable(LdapAccounts::TABLE));
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_directory_reader_lists_all_users_and_marks_nested_group_members_separately(): void
    {
        $allowed = $this->user(['objectcategory' => 'person']);
        $disabled = $this->user(['cn' => 'Disabled', 'samaccountname' => 'disabled',
            'userprincipalname' => 'disabled@example.test', 'objectcategory' => 'person', 'useraccountcontrol' => 514]);
        $outside = $this->user(['cn' => 'Outside', 'samaccountname' => 'outside',
            'userprincipalname' => 'outside@example.test', 'objectcategory' => 'person']);
        \LdapRecord\Models\ActiveDirectory\Computer::create(['cn' => 'Computer', 'objectcategory' => 'computer']);
        $group = Group::create(['cn' => 'Allowed']);
        $child = Group::create(['cn' => 'Nested']);
        $group->members()->attach($child);
        $child->members()->attach($allowed);
        config(['accountmanager_ldap.required_group_dn' => $group->getDn()]);

        $rows = collect(iterator_to_array((new LdapRecordDirectory)->users()))->keyBy(fn ($row) => $row->identity->guid);
        $this->assertCount(3, $rows);
        $this->assertTrue($rows[$allowed->getConvertedGuid()]->inRequiredGroup);
        $this->assertFalse($rows[$disabled->getConvertedGuid()]->enabled);
        $this->assertFalse($rows[$outside->getConvertedGuid()]->inRequiredGroup);
        $this->assertSame('mario', $rows[$allowed->getConvertedGuid()]->samAccountName);
        $this->assertSame($allowed->getDn(), $rows[$allowed->getConvertedGuid()]->dn);
    }
}

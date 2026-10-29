<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Auth\Ldap\DirectoryAuthenticator;
use IlBronza\AccountManager\Auth\Ldap\DirectoryIdentity;
use IlBronza\AccountManager\Auth\Ldap\LdapAccounts;
use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Mockery;

class LdapTestCommandTest extends TestCase
{
    protected function directory(): \Mockery\MockInterface
    {
        $directory = Mockery::mock(DirectoryAuthenticator::class);
        $this->app->instance(DirectoryAuthenticator::class, $directory);

        return $directory;
    }

    protected function identity(): DirectoryIdentity
    {
        return new DirectoryIdentity('default', 'c5171613-1e67-4a79-911b-3ff267ae63a2', 'Test AD user');
    }

    protected function assertLocalStateUnchanged(): void
    {
        $this->assertFalse(config('accountmanager_ldap.enabled'));
        $this->assertFalse(Schema::hasTable(LdapAccounts::TABLE));
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_lookup_works_with_login_disabled_and_without_the_optional_identity_table(): void
    {
        $directory = $this->directory();
        $directory->shouldReceive('find')->once()->with('ad-user')->andReturn($this->identity());
        $directory->shouldNotReceive('authenticate');

        $this->artisan('accountmanager:ldap-test', ['username' => 'ad-user'])
            ->expectsOutput('AD user verified. No local users, associations or sessions changed.')
            ->assertExitCode(0);
        $this->assertLocalStateUnchanged();
    }

    public function test_authentication_prompts_for_a_password_without_logging_in(): void
    {
        $directory = $this->directory();
        $directory->shouldReceive('find')->once()->with('ad-user')->andReturn($this->identity());
        $directory->shouldReceive('authenticate')->once()->with('ad-user', 'user-secret')->andReturn($this->identity());

        $this->artisan('accountmanager:ldap-test', ['username' => 'ad-user', '--authenticate' => true])
            ->expectsQuestion('AD password (input hidden)', 'user-secret')
            ->expectsOutput('AD password verified.')
            ->assertExitCode(0);
        $this->assertLocalStateUnchanged();
    }

    public function test_invalid_user_is_rejected_before_requesting_a_password(): void
    {
        $directory = $this->directory();
        $directory->shouldReceive('find')->once()->with('denied-user')->andReturnNull();
        $directory->shouldNotReceive('authenticate');

        $this->artisan('accountmanager:ldap-test', ['username' => 'denied-user', '--authenticate' => true])
            ->expectsOutput('AD user not found, ambiguous, disabled or outside the required group.')
            ->assertExitCode(1);
        $this->assertLocalStateUnchanged();
    }

    public function test_wrong_password_is_rejected_without_local_changes(): void
    {
        $directory = $this->directory();
        $directory->shouldReceive('find')->once()->with('ad-user')->andReturn($this->identity());
        $directory->shouldReceive('authenticate')->once()->with('ad-user', 'wrong')->andReturnNull();

        $this->artisan('accountmanager:ldap-test', ['username' => 'ad-user', '--authenticate' => true])
            ->expectsQuestion('AD password (input hidden)', 'wrong')
            ->expectsOutput('AD credentials rejected.')
            ->assertExitCode(1);
        $this->assertLocalStateUnchanged();
    }

    public function test_empty_password_cannot_trigger_an_anonymous_bind(): void
    {
        $directory = $this->directory();
        $directory->shouldReceive('find')->once()->with('ad-user')->andReturn($this->identity());
        $directory->shouldNotReceive('authenticate');

        $this->artisan('accountmanager:ldap-test', ['username' => 'ad-user', '--authenticate' => true])
            ->expectsQuestion('AD password (input hidden)', '')
            ->expectsOutput('A non-empty AD password is required.')
            ->assertExitCode(2);
        $this->assertLocalStateUnchanged();
    }

    public function test_directory_failures_do_not_print_sensitive_messages(): void
    {
        $this->withoutMockingConsoleOutput();
        $this->directory()->shouldReceive('find')->once()->andThrow(new \RuntimeException('sensitive-directory-secret'));

        $this->assertSame(1, Artisan::call('accountmanager:ldap-test', ['username' => 'ad-user']));
        $output = Artisan::output();
        $this->assertStringNotContainsString('sensitive-directory-secret', $output);
        $this->assertStringContainsString('LDAP verification unavailable', $output);
        $this->assertLocalStateUnchanged();
    }

    public function test_username_can_be_requested_interactively(): void
    {
        $directory = $this->directory();
        $directory->shouldReceive('find')->once()->with('ad-user')->andReturn($this->identity());
        $directory->shouldNotReceive('authenticate');

        $this->artisan('accountmanager:ldap-test')
            ->expectsQuestion('AD login', 'ad-user')
            ->assertExitCode(0);
        $this->assertLocalStateUnchanged();
    }
}

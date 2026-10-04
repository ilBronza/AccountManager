<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Models\User;
use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class CreateAdminTest extends TestCase
{
    public function test_admin_creation_uses_supplied_credentials(): void
    {
        $this->artisan('accountmanager:create-admin', ['email' => 'admin@example.test', '--name' => 'Admin'])
            ->expectsQuestion('Password', 'a-unique-password')
            ->expectsQuestion('Confirm password', 'a-unique-password')
            ->assertSuccessful();
        $user = User::gpc()::where('email', 'admin@example.test')->firstOrFail();
        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue(Hash::check('a-unique-password', $user->password));
    }

    public function test_existing_account_is_not_silently_promoted(): void
    {
        $user = $this->makeUserWithRole('editor');
        $this->artisan('accountmanager:create-admin', ['email' => $user->email])
            ->expectsQuestion('Password', 'a-unique-password')
            ->expectsQuestion('Confirm password', 'a-unique-password')
            ->assertFailed();
        $this->assertFalse($user->fresh()->isSuperAdmin());
    }
}

<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Helpers\UserCreatorHelper;
use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Models\Userdata;
use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Route;

class UserWritesTest extends TestCase
{
    public function test_slim_creation_activates_user_and_sends_password_setup_link(): void
    {
        Notification::fake();
        Route::get('/reset-password/{token}', fn () => 'reset')->name('password.reset');
        $this->actingAs($admin = $this->makeUserWithRole('superadmin'));
        $this->post('/account-management/users/store-slim', [
            'new_first_name' => 'Mario', 'new_surname' => 'Rossi', 'new_email' => 'mario@example.test',
        ])->assertRedirect();
        $user = $admin::where('email', 'mario@example.test')->firstOrFail();
        $this->assertTrue($user->active);
        $this->assertFalse(Hash::check('Rossi_Mario', $user->password));
        $this->assertSame('Mario', $user->getUserdata()->first_name);
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_creation_rolls_back_when_userdata_cannot_be_saved(): void
    {
        $this->actingAs($this->makeUserWithRole('superadmin'));
        Userdata::creating(fn () => throw new \RuntimeException('userdata unavailable'));
        try {
            UserCreatorHelper::createBySlimParameters('Mario', 'Rossi', 'rollback@example.test', true);
            $this->fail('Expected the userdata write to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('userdata unavailable', $exception->getMessage());
        }
        $this->assertDatabaseMissing('users', ['email' => 'rollback@example.test']);
    }

    public function test_duplicate_excludes_credentials_and_copies_userdata_independently(): void
    {
        $admin = $this->makeUserWithRole('superadmin');
        $this->actingAs($admin);
        $source = $this->makeUserWithRole('editor', 'source@example.test');
        $source->forceFill(['github_id' => '42', 'github_token' => 'secret-token', 'remember_token' => 'secret-session'])->save();
        $source->getUserdata()->forceFill(['first_name' => 'Mario'])->save();
        $this->post('/account-management/users/duplicate/' . $source->id)->assertRedirect();
        $clone = $source::withInactive()->where('email', 'copy_source@example.test')->firstOrFail();
        $this->assertFalse($clone->active);
        $this->assertNotSame($source->password, $clone->password);
        $this->assertNull($clone->github_id);
        $this->assertNull($clone->github_token);
        $this->assertNull($clone->remember_token);
        $this->assertSame('Mario', $clone->getUserdata()->first_name);
        $this->assertNotSame($source->getUserdata()->id, $clone->getUserdata()->id);
        $this->assertSame($source->id, $source->getUserdata()->fresh()->user_id);
        $this->post('/account-management/users/duplicate/' . $source->id)->assertRedirect();
        $this->assertDatabaseCount('users', 4);
    }

    public function test_delete_and_restore_user_return_valid_responses(): void
    {
        $this->actingAs($admin = $this->makeUserWithRole('superadmin'));
        $user = $this->makeUserWithRole('editor', 'target@example.test');
        $user->getUserdata();
        $this->delete('/account-management/users/' . $user->id)->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        $this->delete('/account-management/users/restore/' . $user->id)->assertRedirect('/account-management/users');
        $this->assertNotSoftDeleted('users', ['id' => $user->id]);
        $this->assertNotSoftDeleted('users__userdata', ['user_id' => $user->id]);
        $this->delete('/account-management/users/' . $admin->id)->assertForbidden();
        $this->delete('/account-management/users/restore/99999')->assertNotFound();
    }

    public function test_managers_cannot_change_or_duplicate_superadmins(): void
    {
        $manager = $this->makeUserWithRole('accountManagerUsers');
        $superadmin = $this->makeUserWithRole('superadmin', 'admin@example.test');
        $this->actingAs($manager);
        $this->putJson('/account-management/users/' . $superadmin->id, ['password' => 'unwanted-change-123', 'password_confirmation' => 'unwanted-change-123'])->assertForbidden();
        $this->delete('/account-management/users/' . $superadmin->id)->assertForbidden();
        $this->post('/account-management/users/duplicate/' . $superadmin->id)->assertForbidden();
        $this->putJson('/account-management/users/' . $manager->id, ['roles' => [Role::findByName('superadmin')->id]])->assertForbidden();
    }

    public function test_own_password_change_requires_current_password_and_preserves_hash_on_empty_input(): void
    {
        $user = $this->makeUserWithRole('editor');
        $this->actingAs($user);
        $input = ['name' => 'New name', 'email' => $user->email, 'old_password' => 'wrong', 'password' => 'new-long-password', 'password_confirmation' => 'new-long-password'];
        $this->putJson('/account-management/update-account', $input)->assertUnprocessable()->assertJsonValidationErrors('old_password');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
        $input['old_password'] = 'password';
        $this->put('/account-management/update-account', $input)->assertRedirect();
        $this->assertTrue(Hash::check('new-long-password', $user->fresh()->password));
        $input['password'] = $input['password_confirmation'] = '';
        $this->put('/account-management/update-account', $input)->assertRedirect();
        $this->assertTrue(Hash::check('new-long-password', $user->fresh()->password));
        $this->putJson('/account-management/update-account', ['roles' => [1]])->assertForbidden();
    }

    public function test_logout_get_only_displays_confirmation_and_duplicate_requires_post(): void
    {
        $this->actingAs($user = $this->makeUserWithRole('superadmin'));
        $this->get('/logout')->assertOk()->assertSee('method="POST"', false);
        $this->assertAuthenticatedAs($user);
        $this->get('/account-management/users/duplicate/' . $user->id)->assertStatus(405);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_mutating_endpoints_require_csrf_tokens(): void
    {
        $this->actingAs($user = $this->makeUserWithRole('superadmin'));
        // Laravel normally bypasses CSRF in tests. Disable that bypass only here.
        $this->app->instance('env', 'production');
        foreach (['/logout', '/account-management/heartbeat', '/account-management/users/duplicate/' . $user->id] as $uri) {
            $this->postJson($uri)->assertStatus(419);
        }
        $this->assertDatabaseCount('users', 1);
        $this->app->instance('env', 'testing');
    }
}

<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Models\Permission;
use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class ResourceWritesTest extends TestCase
{
    public function test_permission_create_update_delete_preserves_role_relationships(): void
    {
        $this->actingAs($this->makeUserWithRole('superadmin'));
        $role = Role::findOrCreate('editor');
        $this->post('/account-management/permissions', [
            'name' => 'reports.read', 'guard_name' => 'web', 'roles' => [$role->id],
        ])->assertRedirect();
        $permission = Permission::findByName('reports.read');
        $this->assertTrue($role->fresh()->hasPermissionTo('reports.read'));
        $this->put('/account-management/permissions/' . $permission->id, [
            'name' => 'reports.write', 'guard_name' => 'web', 'roles' => [$role->id],
        ])->assertRedirect();
        $this->assertDatabaseHas('permissions', ['id' => $permission->id, 'name' => 'reports.write']);
        $this->delete('/account-management/permissions/' . $permission->id)->assertRedirect();
        $this->assertSoftDeleted('permissions', ['id' => $permission->id]);
    }

    public function test_regular_user_creation_hashes_password_and_assigns_roles(): void
    {
        $this->actingAs($admin = $this->makeUserWithRole('superadmin'));
        $role = Role::findOrCreate('editor');
        $this->post('/account-management/users', [
            'name' => 'New User', 'email' => 'new@example.test', 'active' => true,
            'password' => 'new-user-password', 'password_confirmation' => 'new-user-password',
            'roles' => [$role->id],
        ])->assertRedirect();
        $user = $admin::where('email', 'new@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('new-user-password', $user->password));
        $this->assertTrue($user->hasRole('editor'));
    }

    public function test_configured_administrator_cannot_assign_superadmin_on_create(): void
    {
        $this->actingAs($this->makeUserWithRole('administrator'));
        config(['accountmanager.routeRoles.accountmanagerusers.store' => ['administrator']]);
        $role = Role::findOrCreate('superadmin');
        $this->postJson('/account-management/users', [
            'name' => 'New admin', 'email' => 'new@example.test', 'active' => true,
            'password' => 'new-user-password', 'password_confirmation' => 'new-user-password',
            'roles' => [$role->id],
        ])->assertUnprocessable()->assertJsonValidationErrors('roles.0');
        $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);
    }

    public function test_credentials_are_hidden_and_slack_webhook_is_configurable(): void
    {
        $user = $this->makeUserWithRole('editor');
        $user->github_token = 'private';
        $user->github_refresh_token = 'private';
        $user->remember_token = 'private';
        foreach (['password', 'remember_token', 'github_token', 'github_refresh_token'] as $key) {
            $this->assertArrayNotHasKey($key, $user->toArray());
        }
        config(['accountmanager.slackWebhookUrl' => null]);
        $this->assertNull($user->routeNotificationForSlack(null));
        config(['accountmanager.slackWebhookUrl' => 'https://example.test/webhook']);
        $this->assertSame('https://example.test/webhook', $user->routeNotificationForSlack(null));
    }
}

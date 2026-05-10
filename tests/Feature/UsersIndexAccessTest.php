<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Models\User;
use IlBronza\AccountManager\Tests\TestCase;

class UsersIndexAccessTest extends TestCase
{
	private function makeUserWithRole(string $roleName): User
	{
		Role::findOrCreate($roleName, 'web');

		$user = User::make();
		$user->name = 'Test User';
		$user->email = $roleName . '@example.test';
		$user->password = bcrypt('password');
		$user->active = true;
		$user->save();

		$user->assignRole($roleName);

		return $user;
	}

	public function test_users_index_denies_non_allowed_roles(): void
	{
		$allowedRoles = ['superadmin', 'accountManagerUsers'];
		$deniedRoles = ['users', 'editor', 'administrator', 'guest'];

		foreach ($allowedRoles as $role)
		{
			$user = $this->makeUserWithRole($role);

			$response = $this->actingAs($user)->get('/account-management/users');

			$this->assertNotSame(
				403,
				$response->getStatusCode(),
				"Role {$role} should not be forbidden (got 403)."
			);
		}

		foreach ($deniedRoles as $role)
		{
			$user = $this->makeUserWithRole($role);

			$response = $this->actingAs($user)->get('/account-management/users');

			$this->assertSame(
				403,
				$response->getStatusCode(),
				"Role {$role} should be forbidden (expected 403)."
			);
		}
	}
}


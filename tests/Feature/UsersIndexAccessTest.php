<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Tests\TestCase;

class UsersIndexAccessTest extends TestCase
{
	public function test_users_index_denies_non_allowed_roles(): void
	{
		$allowedRoles = ['superadmin', 'accountManagerUsers'];
		$deniedRoles = ['users', 'editor', 'administrator', 'guest'];

		foreach ($allowedRoles as $role)
		{
			$user = $this->makeUserWithRole($role, $role . '@example.test');

			$response = $this->actingAs($user)->get('/account-management/users');

			$response->assertOk();
		}

		foreach ($deniedRoles as $role)
		{
			$user = $this->makeUserWithRole($role, $role . '@example.test');

			$response = $this->actingAs($user)->get('/account-management/users');

			$this->assertSame(
				403,
				$response->getStatusCode(),
				"Role {$role} should be forbidden (expected 403)."
			);
		}
	}
}

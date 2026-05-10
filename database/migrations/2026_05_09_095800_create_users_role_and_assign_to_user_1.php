<?php

use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Models\User;
use Illuminate\Database\Migrations\Migration;

class CreateUsersRoleAndAssignToUser1 extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		$roleClass = Role::gpc();
		$userClass = User::gpc();

		$role = $roleClass::where('name', 'accountManagerUsers')
			->where('guard_name', 'web')
			->first();

		if (! $role)
			$role = $roleClass::create([
				'name' => 'accountManagerUsers',
				'guard_name' => 'web'
			]);

		if ($user = $userClass::find(1))
			$user->assignRole($role);
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		$roleClass = Role::gpc();
		$userClass = User::gpc();

		if ($user = $userClass::find(1))
			$user->removeRole('accountManagerUsers');

		if ($role = $roleClass::where('name', 'accountManagerUsers')->where('guard_name', 'web')->first())
			$role->delete();
	}
}


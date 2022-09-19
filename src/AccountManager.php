<?php

namespace IlBronza\AccountManager;

use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Models\User;

class AccountManager
{
    public function manageMenuButtons()
    {
        if(! $menu = app('menu'))
            return;

        $button = $menu->provideButton([
                'text' => 'generals.settings',
                'name' => 'settings',
                'icon' => 'gear',
                'roles' => ['administrator']
            ]);

        $button->setFirst();

        $authButton = $menu->createButton([
            'name' => 'account-manager',
            'icon' => 'user-gear',
            'text' => 'account-manager.accounts'
        ]);

        $usersButton = $menu->createButton([
            'name' => 'users.index',
            'icon' => 'users',
            'text' => 'account-manager.users',
            'href' => route('users.index'),
            'permissions' => ['users.index']
        ]);

        $rolesButton = $menu->createButton([
            'name' => 'roles.index',
            'text' => 'account-manager.roles',
            'icon' => 'graduation-cap',
            'href' => route('roles.index'),
            'permissions' => ['roles.index']
        ]);

        $permissionsButton = $menu->createButton([
            'name' => 'permissions.index',
            'text' => 'account-manager.permissions',
            'icon' => 'user-lock',
            'href' => route('permissions.index'),
            'permissions' => ['permissions.index']
        ]);

        $button->addChild($authButton);

        $authButton->addChild($usersButton);
        $authButton->addChild($rolesButton);
        $authButton->addChild($permissionsButton);
    }

	static function getSuperAdministrator()
	{
		mori(Role::with('users')->get());
		return User::role('superadmin')->get();
	}
}
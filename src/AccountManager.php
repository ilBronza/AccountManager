<?php

namespace IlBronza\AccountManager;

use Auth;
use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Models\User;

class AccountManager
{
    public function getCachedUserById(string $value)
    {
        return cache()->remember(
            'user' . $value,
            3600,
            function() use($value)
            {
                return User::find($value);
            }
        );        
    }

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
            'name' => 'accountManager',
            'icon' => 'user-gear',
            'text' => 'accountManager.accounts'
        ]);

        $usersButton = $menu->createButton([
            'name' => 'users.index',
            'icon' => 'users',
            'text' => 'accountManager.users',
            'href' => route('users.index'),
            'permissions' => ['users.index']
        ]);

        $rolesButton = $menu->createButton([
            'name' => 'roles.index',
            'text' => 'accountManager.roles',
            'icon' => 'graduation-cap',
            'href' => route('roles.index'),
            'permissions' => ['roles.index']
        ]);

        $permissionsButton = $menu->createButton([
            'name' => 'permissions.index',
            'text' => 'accountManager.permissions',
            'icon' => 'user-lock',
            'href' => route('permissions.index'),
            'permissions' => ['permissions.index']
        ]);

        $button->addChild($authButton);

        $authButton->addChild($usersButton);
        $authButton->addChild($rolesButton);
        $authButton->addChild($permissionsButton);


        if(Auth::user())
            $account = $menu->provideButton([
                'name' => 'account',
                'translatedText' => Auth::user()->getName(),
                'href' => route('users.show', [Auth::user()]),
                'children' => [
                    [
                        'text' => 'accountManager.edit',
                        'href' => route('users.edit', [Auth::user()])
                    ],
                    [
                        'text' => 'accountManager.editUserdata',
                        'href' => route('accountManager.editUserdata')
                    ],
                    [
                        'text' => 'accountManager.editPassword',
                        'href' => route('password.request')
                    ],
                    [
                        'text' => 'accountManager.logout',
                        'href' => route('accountManager.logout'),
                    ]
                ]
            ]);

    }
}
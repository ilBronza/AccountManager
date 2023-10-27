<?php

namespace IlBronza\AccountManager;

use Auth;
use IlBronza\AccountManager\Models\User;
use IlBronza\CRUD\Providers\RouterProvider\IbRouter;
use IlBronza\CRUD\Providers\RouterProvider\RoutedObjectInterface;
use IlBronza\CRUD\Traits\IlBronzaPackages\IlBronzaPackagesTrait;

class AccountManager implements RoutedObjectInterface
{
    use IlBronzaPackagesTrait;
    static $packageConfigPrefix = 'accountmanager';

    public function getCachedUserById(string $value)
    {
        return cache()->remember(
            'user' . $value,
            3600,
            function() use($value)
            {
                return User::getProjectClassName()::find($value);
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
            'name' => 'accountmanager',
            'icon' => 'user-gear',
            'text' => 'accountmanager.accounts'
        ]);

        $usersButton = $menu->createButton([
            'name' => 'users.index',
            'icon' => 'users',
            'text' => 'accountmanager.users',
            'href' => IbRouter::route($this, 'users.index'),
            'permissions' => ['users.index']
        ]);

        $rolesButton = $menu->createButton([
            'name' => 'roles.index',
            'text' => 'accountmanager.roles',
            'icon' => 'graduation-cap',
            'href' => IbRouter::route($this, 'roles.index'),
            'permissions' => ['roles.index']
        ]);

        $permissionsButton = $menu->createButton([
            'name' => 'permissions.index',
            'text' => 'accountmanager.permissions',
            'icon' => 'user-lock',
            'href' => IbRouter::route($this, 'permissions.index'),
            'permissions' => ['permissions.index']
        ]);

        $button->addChild($authButton);

        $authButton->addChild($usersButton);
        $authButton->addChild($rolesButton);
        $authButton->addChild($permissionsButton);


        if(Auth::user())
        {
            $account = $menu->provideButton([
                'name' => 'account',
                'image' => (($avatar = Auth::user()->getAvatarImage()) ? $avatar : null),
                'translatedText' => Auth::user()->getName(),
                'href' => IbRouter::route($this, 'users.show', [Auth::user()]),

                'children' => [
                    [
                        'text' => 'accountmanager.edit',
                        'href' => IbRouter::route($this, 'accountmanager.account')
                    ],
                    [
                        'text' => 'accountmanager.editUserdata',
                        'href' => IbRouter::route($this, 'accountmanager.editUserdata')
                    ],
                    [
                        'text' => 'accountmanager.resetPassword',
                        'href' => route('password.request')
                    ],
                    [
                        'text' => 'accountmanager.logout',
                        'href' => IbRouter::route($this, 'accountmanager.logout')
                    ]
                ]
            ]);

            $destra = $menu->provideMainRightBar();

            $menu->addToNavbar($account, $destra);

            $account->setFirst();
        }
    }
}
<?php

namespace IlBronza\AccountManager;

use Auth;
use IlBronza\AccountManager\Models\User;
use IlBronza\CRUD\Providers\RouterProvider\IbRouter;
use IlBronza\CRUD\Providers\RouterProvider\RoutedObjectInterface;
use IlBronza\CRUD\Traits\IlBronzaPackages\IlBronzaPackagesTrait;

use function config;
use function route;

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

	    if($user = Auth::user())
	    {
		    $userAreaChildren = [
			    [
				    'text' => 'accountmanager::accountmanager.edit',
				    'href' => IbRouter::route($this, 'accountmanager.account')
			    ]
		    ];

		    if(config('accountmanager.usesUserdata', true))
			    $userAreaChildren[] =
				    [
					    'text' => 'accountmanager::accountmanager.editUserdata',
					    'href' => IbRouter::route($this, 'accountmanager.editUserdata')
				    ];

		    if(config('accountmanager.usesAvatar', true))
			    $userAreaChildren[] =
				    [
					    'text' => 'accountmanager::accountmanager.editAvatar',
					    'href' => IbRouter::route($this, 'accountmanager.editAvatar')
				    ];

		    if(config('accountmanager.canResetPassword', true))
			    $userAreaChildren[] =
				    [
					    'text' => 'accountmanager::accountmanager.resetPassword',
					    'href' => route('password.request')
				    ];

		    $userAreaChildren[] =
			    [
				    'text' => 'accountmanager::accountmanager.logout',
				    'href' => IbRouter::route($this, 'accountmanager.logout')
			    ];

		    $userAreaParameters = [
			    'name' => 'account',
			    'image' => (($avatar = Auth::user()->getAvatarImage()) ? $avatar : null),
			    'translatedText' => Auth::user()->getName(),
			    'href' => IbRouter::route($this, 'users.show', [Auth::user()]),

			    'children' => $userAreaChildren
		    ];

		    $account = $menu->provideButton($userAreaParameters);

		    $destra = $menu->provideMainRightBar();

		    $menu->addToNavbar($account, $destra);

		    $account->setFirst();
	    }

		if(! ($user?->isAdministrator())&&(! ($user = Auth::user())?->isSuperadmin()))
			return ;

        if(config('accountmanager.enabled', false))
        {
            $settingsButton = $menu->provideButton([
                    'text' => 'generals.settings',
                    'name' => 'settings',
                    'icon' => 'gear',
                    'roles' => ['administrator']
                ]);

            $settingsButton->setFirst();

            $authButton = $menu->createButton([
                'name' => 'accountmanager',
                'icon' => 'user-gear',
                'text' => 'accountmanager::accountmanager.accounts'
            ]);

            $settingsButton->addChild($authButton);

            $usersButton = $menu->createButton([
                'name' => 'users.index',
                'icon' => 'users',
                'text' => 'accountmanager::accountmanager.users',
                'href' => IbRouter::route($this, 'users.index'),
                'permissions' => ['users.index']
            ]);

            $authButton->addChild($usersButton);

            if($user->isSuperadmin())
            {
                $rolesButton = $menu->createButton([
                    'name' => 'roles.index',
                    'text' => 'accountmanager::accountmanager.roles',
                    'icon' => 'graduation-cap',
                    'href' => IbRouter::route($this, 'roles.index'),
                    'permissions' => ['roles.index']
                ]);

                if(config('app.usesPermissions', true))
                    $permissionsButton = $menu->createButton([
                        'name' => 'permissions.index',
                        'text' => 'accountmanager::accountmanager.permissions',
                        'icon' => 'user-lock',
                        'href' => IbRouter::route($this, 'permissions.index'),
                        'permissions' => ['permissions.index']
                    ]);                

                $authButton->addChild($rolesButton);

                if(config('app.usesPermissions', true))
                    $authButton->addChild($permissionsButton);
            }

            try
            {
                if(app('mailer')&&(config('mailer.active', true)))
                {
                    $mailersButton = $menu->createButton([
                        'name' => 'mailers.index',
                        'text' => 'mailer::mailer.index',
                        'icon' => 'user-lock',
                        'href' => route('usermailers.index'),
                        'roles' => ['administrator']
                    ]);

                    $authButton->addChild($mailersButton);

                }
            }
            catch(\Exception $e)
            {
                // dd($e->getMessage());
            }


        }
	}
}
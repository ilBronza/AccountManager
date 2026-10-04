<?php

namespace IlBronza\AccountManager\Http\Controllers\Users;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use IlBronza\Ukn\Ukn;

class DuplicateUserController extends BaseUserPackageController
{
    public $allowedMethods = ['duplicate'];

    public function duplicate($user)
    {
        $user = $this->getUserModel($user);
        // Copying privileged accounts is itself a privileged operation.
        abort_if($user->isSuperAdmin() && ! Auth::user()->isSuperAdmin(), 403);

        $user->getConnection()->transaction(function () use ($user) {
            $clone = $user->replicate([
                'password', 'remember_token', 'github_id', 'github_token',
                'github_refresh_token', 'email_verified_at', 'deleted_at',
            ]);
            $clone->unsetRelations();
            $clone->name = 'copy_' . $user->name;
            $clone->email = 'copy_' . $user->email;
            if ($user->newQueryWithoutScopes()->where('email', $clone->email)->exists()) {
                $clone->email = 'copy_' . Str::uuid() . '@' . (parse_url(config('app.url'), PHP_URL_HOST) ?: 'example.invalid');
            }
            $clone->password = Str::random(64);
            $clone->active = false;
            $clone->save();
            $clone->syncRoles($user->roles);
            $clone->syncPermissions($user->permissions);

            if ($userdata = $user->userdata) {
                $data = $userdata->replicate(['id', 'user_id']);
                $data->unsetRelations();
                $data->user_id = $clone->getKey();
                $data->save();
            }
        });

        Ukn::s(trans('accountmanager::accountmanager.userCloned', ['user' => $user->name]));

        return back();
    }
}

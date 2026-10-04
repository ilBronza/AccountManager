<?php

namespace IlBronza\AccountManager\Http\Traits;

use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

trait ValidatesUserAccessChanges
{
    protected function validateUserChanges(Request $request, ?User $target = null): void
    {
        $actor = $request->user();
        abort_if($target?->isSuperAdmin() && ! $actor->isSuperAdmin(), 403);

        if (! $actor->isSuperAdmin() && ! $actor->isAdministrator()) {
            foreach (['roles', 'permissions', 'active', 'allow_from_remote', 'allowed_ips'] as $field) {
                abort_if($request->exists($field), 403);
            }
        }
        if (! $actor->isSuperAdmin()) {
            $ids = Role::gpc()::where('name', 'superadmin')->pluck('id')->all();
            $request->validate(['roles' => ['sometimes', 'array'], 'roles.*' => [Rule::notIn($ids)]]);
        }
        $rules = ['password' => ['nullable', 'string', 'confirmed', Password::min(12)]];
        if ($request->exists('email')) {
            $model = $target ?? new (User::gpc());
            $rules['email'] = ['required', 'email', 'max:191', Rule::unique($model->getTable(), 'email')->ignore($target)];
        }
        $request->validate($rules);
    }
}

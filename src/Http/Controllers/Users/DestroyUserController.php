<?php

namespace IlBronza\AccountManager\Http\Controllers\Users;

use IlBronza\CRUD\Traits\CRUDDeleteTrait;
use IlBronza\CRUD\Traits\CRUDDestroyTrait;

class DestroyUserController extends BaseUserPackageController
{
    use CRUDDestroyTrait;
    use CRUDDeleteTrait;

    public $allowedMethods = ['destroy', 'restore'];

    public function restore($user)
    {
        $model = $this->getModelClass()::withInactive()->onlyTrashed()->findOrFail($user);
        abort_if($model->isSuperAdmin() && ! \Auth::user()->isSuperAdmin(), 403);
        $model->getConnection()->transaction(function () use ($model) {
            $model->restore();
            $model->userdata()->onlyTrashed()->restore();
        });

        return redirect()->to(app('accountmanager')->route('users.index'))
            ->with('status', __('accountmanager::auth.userRestored', ['user' => $model->name]));
    }

    public function destroy($user)
    {
        $user = $this->getUserModel($user);

        if($user->getKey() == \Auth::id())
            abort(403, 'Non puoi cancellare te stesso');

        abort_if($user->isSuperAdmin() && ! \Auth::user()->isSuperAdmin(), 403);

        return $user->getConnection()->transaction(fn () => $this->_destroy($user));
    }
}

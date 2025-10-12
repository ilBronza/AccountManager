<?php

namespace IlBronza\AccountManager\Http\Controllers\Users;

use IlBronza\CRUD\Models\Scopes\ActiveScope;
use IlBronza\CRUD\Traits\CRUDDeleteTrait;
use IlBronza\CRUD\Traits\CRUDDestroyTrait;

class DestroyUserController extends BaseUserPackageController
{
    use CRUDDestroyTrait;
    use CRUDDeleteTrait;

    public $allowedMethods = ['destroy', 'restore'];

    public function restore($user)
    {
        $model = $this->getModelClass()::onlyTrashed()->find($user);

        $model->restore();

        dd($model);
    }

    public function destroy($user)
    {
        $user = $this->getUserModel($user);

        if($user->getKey() == \Auth::id())
            abort(403, 'Non puoi cancellare te stesso');

        return $this->_destroy($user);
    }
}

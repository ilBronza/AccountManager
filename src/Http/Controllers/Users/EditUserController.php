<?php

namespace IlBronza\AccountManager\Http\Controllers\Users;

use Auth;
use IlBronza\AccountManager\Models\Role;
use IlBronza\CRUD\Models\Scopes\ActiveScope;
use IlBronza\CRUD\Providers\RouterProvider\IbRouter;
use IlBronza\CRUD\Traits\CRUDEditUpdateTrait;
use Illuminate\Http\Request;

class EditUserController extends BaseUserPackageController
{
    use CRUDEditUpdateTrait;

	public ?bool $updateEditor = false;

    public $allowedMethods = [
        'edit',
        'update'
    ];

    public function getGenericParametersFile() : ? string
    {
        return config('accountmanager.models.user.parametersFiles.edit');
    }

    public function getUserModel(int|string $user)
    {
        return $this->getModelClass()::withoutGlobalScope(ActiveScope::class)->where('id', $user)->first();
    }

    public function edit($user)
    {
        $user = $this->getUserModel($user);

        return $this->_edit($user);
    }

    public function getAfterUpdatedRedirectUrl()
    {
        return IbRouter::route(app('accountmanager'), 'users.index');
    }

    public function getUpdateModelAction()
    {
        return IbRouter::route(app('accountmanager'), 'users.update', ['user' => $this->getModel()->getKey()]);
        // return route('accountmanager.update');
    }

    public function avoidSuperadminAssignment(Request $request)
    {
        $superadminRoles = Role::select('id')->where('name', 'superadmin')->pluck('id');

        $idsString = $superadminRoles->implode(',');

        $request->validate([
            'roles.*' => 'nullable|notIn:' . $idsString
        ], ['roles.*.not_in' => ';-)']);
    }

    public function update(Request $request, $user)
    {
        if(! Auth::user()->isSuperadmin())
            $this->avoidSuperadminAssignment($request);

        $user = $this->getUserModel($user);

        return $this->_update($request, $user);
    }
}

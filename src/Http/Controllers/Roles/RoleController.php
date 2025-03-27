<?php

namespace IlBronza\AccountManager\Http\Controllers\Roles;

use Auth;
use IlBronza\AccountManager\Models\Role as Role;
use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Providers\RouterProvider\IbRouter;
use IlBronza\CRUD\Traits\CRUDBelongsToManyTrait;
use IlBronza\CRUD\Traits\CRUDCreateStoreTrait;
use IlBronza\CRUD\Traits\CRUDDeleteTrait;
use IlBronza\CRUD\Traits\CRUDDestroyTrait;
use IlBronza\CRUD\Traits\CRUDEditUpdateTrait;
use IlBronza\CRUD\Traits\CRUDIndexTrait;
use IlBronza\CRUD\Traits\CRUDPlainIndexTrait;
use IlBronza\CRUD\Traits\CRUDRelationshipTrait;
use IlBronza\CRUD\Traits\CRUDShowTrait;
use Illuminate\Http\Request;

use function config;

class RoleController extends CRUD
{

    use CRUDShowTrait;

    use CRUDPlainIndexTrait;
    use CRUDIndexTrait;
    use CRUDEditUpdateTrait;
    use CRUDCreateStoreTrait;

    use CRUDDeleteTrait;
    use CRUDDestroyTrait;

    use CRUDRelationshipTrait;
    use CRUDBelongsToManyTrait;

    public $modelClass = 'IlBronza\AccountManager\Models\Role';

    public $allowedMethods = ['index', 'show', 'edit', 'update', 'create', 'store', 'delete', 'destroy'];

    public function getIndexFieldsArray()
    {
        return config('accountmanager.models.role.fieldsGroupsFiles.index')::getFieldsGroup();
    }

    public function getRelatedFieldsArray()
    {
        return config('accountmanager.models.role.fieldsGroupsFiles.related')::getFieldsGroup();
    }

    public function getGenericParametersFile() : ? string
    {
        return config('accountmanager.models.role.parametersFiles.create');
    }

    public function getIndexElements()
    {
        if(Auth::user()->hasRole('superadmin'))
            return Role::all();

        return Role::where('name', '!=', 'superadmin')->get();
    }

    public function show(Role $role)
    {
        return $this->_show($role);
    }

    public function edit(Role $role)
    {
        return $this->_edit($role);
    }

    public function getUpdateModelAction()
    {
        return IbRouter::route(app('accountmanager'), 'roles.update', ['role' => $this->getModel()->getKey()]);
        // return route('accountmanager.update');
    }

    public function getStoreModelAction()
    {
        return IbRouter::route(app('accountmanager'), 'roles.store');
        // return route('accountmanager.update');
    }

    public function getAfterStoredRedirectUrl()
    {
        return IbRouter::route(app('accountmanager'), 'roles.index');
    }

    public function getAfterUpdatedRedirectUrl()
    {
        return IbRouter::route(app('accountmanager'), 'roles.index');
    }

    public function update(Request $request, Role $role)
    {
        return $this->_update($request, $role);
    }

    public function delete(Role $role)
    {
        return $this->_delete($role);
    }

    public function destroy(Role $role)
    {
        return $this->_destroy($role);
    }
}

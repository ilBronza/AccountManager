<?php

namespace IlBronza\AccountManager\Http\Controllers\Permissions;

use IlBronza\AccountManager\Http\Traits\CRUDPermissionParametersTrait;
use IlBronza\AccountManager\Models\Permission as Permission;
use Illuminate\Http\Request;
use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Traits\CRUDBelongsToManyTrait;
use IlBronza\CRUD\Traits\CRUDCreateStoreTrait;
use IlBronza\CRUD\Traits\CRUDDeleteTrait;
use IlBronza\CRUD\Traits\CRUDDestroyTrait;
use IlBronza\CRUD\Traits\CRUDEditUpdateTrait;
use IlBronza\CRUD\Traits\CRUDIndexTrait;
use IlBronza\CRUD\Traits\CRUDPlainIndexTrait;
use IlBronza\CRUD\Traits\CRUDRelationshipTrait;
use IlBronza\CRUD\Traits\CRUDShowTrait;

class PermissionController extends CRUD
{
    use CRUDPermissionParametersTrait;

    use CRUDShowTrait;

    use CRUDPlainIndexTrait;
    use CRUDIndexTrait;
    use CRUDEditUpdateTrait;
    use CRUDCreateStoreTrait;

    use CRUDDeleteTrait;
    use CRUDDestroyTrait;

    use CRUDRelationshipTrait;
    use CRUDBelongsToManyTrait;

    public $modelClass = 'IlBronza\AccountManager\Models\Permission';

    public $allowedMethods = ['index', 'show', 'edit', 'update', 'create', 'store', 'delete'];

    public $guardedShowDBFields = ['id', 'created_at', 'updated_at'];

    protected $relationshipsControllers = [
        'roles' => '\IlBronza\AccountManager\Http\Controllers\RoleController'
    ];

    public function getIndexFieldsArray()
    {
        return config('accountmanager.models.permission.fieldsGroupsFiles.index')::getFieldsGroup();
    }

    public function getRelatedFieldsArray()
    {
        return config('accountmanager.models.permission.fieldsGroupsFiles.related')::getFieldsGroup();
    }

    public function getIndexElements()
    {
        return Permission::all();
    }

    public function show(Permission $permission)
    {
        return $this->_show($permission);
    }

    public function edit(Permission $permission)
    {
        return $this->_edit($permission);
    }

    public function update(Request $request, Permission $permission)
    {
        return $this->_update($request, $permission);
    }

    public function delete(Permission $permission)
    {
        return $this->_delete($permission);
    }
}

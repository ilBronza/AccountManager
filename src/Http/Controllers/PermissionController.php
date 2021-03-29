<?php

namespace IlBronza\AccountManager\Http\Controllers;

use IlBronza\AccountManager\Http\Traits\CRUDPermissionParametersTrait;
use IlBronza\AccountManager\Models\Permission as Permission;
use Illuminate\Http\Request;
use ilBronza\CRUD\CRUD;
use ilBronza\CRUD\Traits\CRUDBelongsToManyTrait;
use ilBronza\CRUD\Traits\CRUDCreateStoreTrait;
use ilBronza\CRUD\Traits\CRUDDeleteTrait;
use ilBronza\CRUD\Traits\CRUDDestroyTrait;
use ilBronza\CRUD\Traits\CRUDEditUpdateTrait;
use ilBronza\CRUD\Traits\CRUDIndexTrait;
use ilBronza\CRUD\Traits\CRUDPlainIndexTrait;
use ilBronza\CRUD\Traits\CRUDRelationshipTrait;
use ilBronza\CRUD\Traits\CRUDShowTrait;

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

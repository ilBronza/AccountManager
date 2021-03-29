<?php

namespace IlBronza\AccountManager\Http\Controllers;

use IlBronza\AccountManager\Http\Traits\CRUDRoleParametersTrait;
use IlBronza\AccountManager\Models\Role as Role;
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

class RoleController extends CRUD
{
    use CRUDRoleParametersTrait;

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

    public $allowedMethods = ['index', 'show', 'edit', 'update', 'create', 'store', 'delete'];

    public $guardedShowDBFields = ['id', 'created_at', 'updated_at'];

    protected $relationshipsControllers = [
        'permissions' => '\IlBronza\AccountManager\Http\Controllers\PermissionController'
    ];

    public function getIndexElements()
    {
        return Role::all();
    }

    public function show(Role $role)
    {
        return $this->_show($role);
    }

    public function edit(Role $role)
    {
        return $this->_edit($role);
    }

    public function update(Request $request, Role $role)
    {
        return $this->_update($request, $role);
    }

    public function delete(Role $role)
    {
        return $this->_delete($role);
    }
}

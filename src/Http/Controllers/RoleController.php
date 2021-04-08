<?php

namespace IlBronza\AccountManager\Http\Controllers;

use IlBronza\AccountManager\Http\Traits\CRUDRoleParametersTrait;
use IlBronza\AccountManager\Models\Role as Role;
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

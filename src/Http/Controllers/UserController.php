<?php

namespace IlBronza\AccountManager\Http\Controllers;

use App\Models\User;
use IlBronza\AccountManager\Http\Traits\CRUDUserParametersTrait;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

class UserController extends CRUD
{
    use CRUDUserParametersTrait;

    use CRUDShowTrait;
    use CRUDPlainIndexTrait;
    use CRUDIndexTrait;
    use CRUDEditUpdateTrait;
    use CRUDCreateStoreTrait;

    use CRUDDeleteTrait;
    use CRUDDestroyTrait;

    use CRUDRelationshipTrait;
    use CRUDBelongsToManyTrait;

    public $modelClass = '\App\Models\User';

    public $allowedMethods = [
        'index',
        'show',
        'edit',
        'update',
        'create',
        'store',
        'destroy',
        'forceDelete'
    ];

    // public $guardedEditDBFields = ['id', 'created_at', 'updated_at', 'deleted_at'];
    // public $guardedCreateDBFields = ['id', 'created_at', 'updated_at', 'deleted_at'];
    public $guardedShowDBFields = ['id', 'created_at', 'updated_at', 'deleted_at'];

    public $showMethodRelationships = [
        // 'permissions',
        // 'roles',
        // 'cities'
    ];

    public $relationshipsControllers = [
        'roles' => '\IlBronza\AccountManager\Http\Controllers\RoleController',
        // 'cities' => '\App\Http\Controllers\Address\CityController'
    ];

    public function getIndexElements()
    {
        return User::all();
        return User::with($this->showMethodRelationships)->withTrashed()->get();
    }

    public function show(User $user)
    {
        return $this->_show($user);
    }

    public function edit(User $user)
    {
        return $this->_edit($user);
    }

    public function update(Request $request, User $user)
    {
        return $this->_update($request, $user);
    }

    public function destroy(User $user)
    {
        return $this->_delete($user);
    }

    public function setBeforeStoreFields(array $parameters)
    {
        $this->modelInstance->password = Hash::make($parameters['password']);
    }

}

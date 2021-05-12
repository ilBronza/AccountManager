<?php

namespace IlBronza\AccountManager\Http\Controllers;

use App\Models\User;
use IlBronza\AccountManager\Http\Traits\CRUDUserParametersTrait;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
        'roles',
        // 'cities'
    ];

    public $relationshipsControllers = [
        'contacts' => '\App\Http\Controllers\Contacts\CRUDContactController',
        'roles' => '\IlBronza\AccountManager\Http\Controllers\RoleController'
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

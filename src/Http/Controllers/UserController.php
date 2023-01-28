<?php

namespace IlBronza\AccountManager\Http\Controllers;

use App\Models\ProjectSpecific\User;
use IlBronza\AccountManager\Http\Traits\CRUDUserParametersTrait;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

    public $modelClass = User::class;

    public $allowedMethods = [
        'index',
        'show',
        'edit',
        'update',
        'create',
        'store',
        'delete',
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

    public function getTableFieldsGroup(string $key)
    {
        return config('accountmanager.indexFields');
    }

    public function getFormFieldsets(string $type)
    {
        $result = array_merge_recursive(
            config('accountmanager.formFields.common', []),
            config('accountmanager.formFields.' . $type, [])
        );

        if($type == 'editor')
            $result = array_merge_recursive(
                config('accountmanager.formFields.edit', []),
                $result
            );

        return $result;
    }

    public function show(User $user)
    {
        return $this->_show($user);
    }

    public function edit(User $user)
    {
        return $this->_edit($user);
    }

    public function validateUpdateRequest(Request $request)
    {
        $parameters = $this->validateRequestByType($request, 'update');

        if((array_key_exists('password', $parameters))&&(is_null($parameters['password'])))
            unset($parameters['password']);

        if((array_key_exists('password_confirmation', $parameters))&&(is_null($parameters['password_confirmation'])))
            unset($parameters['password_confirmation']);

        return $parameters;
    }

    public function update(Request $request, User $user)
    {
        return $this->_update($request, $user);
    }

    public function destroy(User $user)
    {
        return $this->_destroy($user);
    }

    public function delete(User $user)
    {
        return $this->_delete($user);
    }
}

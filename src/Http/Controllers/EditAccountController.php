<?php

namespace IlBronza\AccountManager\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use ilBronza\CRUD\CRUD;
use ilBronza\CRUD\Traits\CRUDEditUpdateTrait;

class EditAccountController extends CRUD
{
    use CRUDEditUpdateTrait;

    static $formFields = [
        'edit' => [
            'default' => [
                'name' => ['text' => 'string|required|max:191'],
                'email' => ['email' => 'email|required|max:191'],
                'password' => ['password' => 'string|nullable|confirmed|max:191'],
                'password_confirmation' => ['password' => 'string|nullable'],
            ]
        ]
    ];    

    public $allowedMethods = ['edit', 'update'];
    public $modelClass = '\App\User';


	public function edit()
	{
		return $this->_edit(
			Auth::user()
		);
	}

	public function getUpdateModelAction()
	{
		return route('accountManager.update');
	}

	public function manageModelInstanceAfterUpdate(array $parameters)
	{
		if($password = $parameters['password'] ?? false)
			$this->modelInstance->update(['password'=> Hash::make($password)]);
	}

	public function update(Request $request)
	{
		return $this->_update($request, Auth::user());
	}

}
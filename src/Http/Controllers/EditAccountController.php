<?php

namespace IlBronza\AccountManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Auth;
use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Traits\CRUDEditUpdateTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

    public $allowedMethods = ['edit', 'update', 'index'];
    public $modelClass = '\App\Models\User';


    public function index()
    {
    	dd('deh');
    }

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

	public function update(Request $request)
	{
		throw new \Exception ('Ricordare di impostare mutator per password');
		// public function manageModelInstanceAfterUpdate(array $parameters)
		// {
		// 	if($password = $parameters['password'] ?? false)
		// 		$this->modelInstance->update(['password'=> Hash::make($password)]);
		// }


		return $this->_update($request, Auth::user());
	}

}
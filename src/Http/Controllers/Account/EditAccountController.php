<?php

namespace IlBronza\AccountManager\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Auth;
use IlBronza\AccountManager\Models\User;
use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Providers\RouterProvider\IbRouter;
use IlBronza\CRUD\Traits\CRUDEditUpdateTrait;
use IlBronza\Ukn\Facades\Ukn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EditAccountController extends CRUD
{
    use CRUDEditUpdateTrait;

    public $allowedMethods = [
    	'edit',
    	'update'
    ];

    public function getGenericParametersFile() : ? string
    {
		return config('accountmanager.models.user.parametersFiles.editAccount');
    }

    public function setModelClass()
    {
        $this->modelClass = User::getProjectClassName();
    }

	public function edit()
	{
		return $this->_edit(
			Auth::user()
		);
	}

	public function getUpdateModelAction()
	{
		return IbRouter::route(app('accountmanager'), 'accountmanager.update');
		// return route('accountmanager.update');
	}

	public function sendUpdateSuccessMessage()
	{
		Ukn::s(trans('accountmanager::auth.passwordSuccessfullyReset'));
	}

	public function getAfterUpdatedRedirectUrl()
	{
		return IbRouter::route(app('accountmanager'), 'users.show', ['user' => Auth::id()]);
	}

	public function update(Request $request)
	{
		if($request->password)
		{
			if(! Hash::check($request->old_password, Auth::user()->password))
				return back()->withErrors(['old_password' => 'Vecchia password sbagliata. Se non ricordi la vecchia password, vai nel menu utente e procedi con la voce "Reset password"']);
					}
		else
			$request->request->remove('password');

		$request->request->remove('old_password');

		return $this->_update($request, Auth::user());
	}

}
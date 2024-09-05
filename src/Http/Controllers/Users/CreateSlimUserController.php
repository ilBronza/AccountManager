<?php

namespace IlBronza\AccountManager\Http\Controllers\Users;

use IlBronza\AccountManager\Helpers\UserCreatorHelper;
use IlBronza\AccountManager\Http\Controllers\Users\CreateUserController;
use IlBronza\AccountManager\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CreateSlimUserController extends CreateUserController
{
    public $allowedMethods = ['create', 'store'];

    public $returnBack = true;

    public function getGenericParametersFile() : ? string
    {
        return config('accountmanager.models.user.parametersFiles.createSlim');
    }

    public function getStoreModelAction()
    {
        return app('accountmanager')->route('users.storeSlim');
    }

    public function getAfterStoredRedirectUrl()
    {
        return $this->getModel()->getEditUserdataUrl();
    }

    public function store(Request $request)
    {
        $request->validate([
            'new_first_name' => 'string|required|max:191',
            'new_surname' => 'string|required|max:191',
            'new_email' => 'email|required|max:191|unique:users,email'
        ]);

		UserCreatorHelper::createBySlimParameters(
			$request->new_first_name,
			$request->new_surname,
			$request->new_email,
			true
		);

        return redirect()->to(
            $this->getReturnUrl()
        );
    }
}

<?php

namespace IlBronza\AccountManager\Http\Controllers\Users;

use IlBronza\AccountManager\Helpers\UserCreatorHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

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

    public function store(Request $request, string $model = null)
    {
        $request->validate([
            'new_first_name' => 'string|required|max:64',
            'new_surname' => 'string|required|max:64',
            'new_email' => 'email|required|max:191|unique:users,email'
        ]);

		$user = UserCreatorHelper::createBySlimParameters(
			$request->new_first_name,
			$request->new_surname,
			$request->new_email,
			true
		);

        $this->setModel($user);
        $status = Password::sendResetLink(['email' => $user->email]);

        return redirect()->to($this->getReturnUrl() ?: app('accountmanager')->route('users.index'))
            ->with('status', __($status));
    }
}

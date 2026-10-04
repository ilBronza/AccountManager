<?php

namespace IlBronza\AccountManager\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Auth;
use IlBronza\AccountManager\Models\User;
use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Providers\RouterProvider\IbRouter;
use IlBronza\CRUD\Traits\CRUDEditUpdateTrait;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;
use IlBronza\Ukn\Ukn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EditAccountController extends CRUD
{
    public $returnBack = true;

	public ?bool $updateEditor = false;

    use CRUDEditUpdateTrait;

    public $allowedMethods = [
    	'edit',
    	'update'
    ];

    public function getGenericParametersFile() : ? string
    {
		return config('accountmanager.models.user.parametersFiles.editAccount');
    }

	public function getOverriddenEditParametersFile() : ?string
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
		return '/';
		// return IbRouter::route(app('accountmanager'), 'users.show', ['user' => Auth::id()]);
	}

    public function update(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'email' => ['sometimes', 'required', 'email', \Illuminate\Validation\Rule::unique($user->getTable(), 'email')->ignore($user)],
            'password' => ['nullable', 'string', 'confirmed', \Illuminate\Validation\Rules\Password::min(12)],
            'old_password' => $request->filled('password') ? ['required', 'string', 'current_password'] : ['nullable'],
        ]);
        if (! $request->filled('password')) {
            $request->request->remove('password');
        }
        $request->request->remove('old_password');
        foreach (['roles', 'permissions', 'active', 'allow_from_remote', 'allowed_ips', 'github_id'] as $field) {
            abort_if($request->exists($field), 403);
        }

        return $user->getConnection()->transaction(fn () => $this->_update($request, $user));
    }
}

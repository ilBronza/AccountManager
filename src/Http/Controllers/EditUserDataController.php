<?php

namespace IlBronza\AccountManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Auth;
use IlBronza\AccountManager\Models\Userdata;
use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Traits\CRUDEditUpdateTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EditUserDataController extends CRUD
{
    use CRUDEditUpdateTrait;

    static $formFields = [
        'edit' => [
            'default' => [
                'first_name' => ['text' => 'string|required|max:64'],
                'surname' => ['text' => 'string|required|max:64'],

                'fiscal_code' => ['text' => 'string|nullable|max:16'],
                'birth_date' => ['date' => 'date|nullable'],
                'gender' => ['select' => 'string|nullable'],

                'avatar' => [
                    'type' => 'file',
                    'cropper' => [
                    ],
                    'disk' => [
                        'method' => 'getAvatarDisk'
                    ],
                    'multiple' => false,
                    'rules' =>'string|nullable'
                ],
            ]
        ]
    ];

    public $allowedMethods = ['edit', 'update'];
    public $modelClass = Userdata::class;

	public function edit()
	{
		$userdata = Auth::user()->getUserData();

		return $this->_edit($userdata);
	}

	public function getUpdateModelAction()
	{
		return route('accountManager.updateUserdata');
	}

	public function update(Request $request)
	{
		$userdata = Auth::user()->refreshUserdata();

		return $this->_update($request, $userdata);
	}

}
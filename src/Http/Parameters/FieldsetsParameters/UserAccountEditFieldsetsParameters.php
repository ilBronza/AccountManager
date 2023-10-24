<?php

namespace IlBronza\AccountManager\Http\Parameters\FieldsetsParameters;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;

class UserAccountEditFieldsetsParameters extends FieldsetParametersFile
{
    public function _getFieldsetsParameters() : array
    {
        return [
            'base' => [
                'translationPrefix' => 'accountmanager',
                'fields' => [
                    'name' => ['text' => 'string|required|max:191'],
                    'email' => ['email' => 'email|required|max:191'],
                    //'password' => ['required', 'confirmed', Rules\Password::defaults()],
                    'old_password' => ['password' => 'string|max:255'],
                    'password' => ['password' => 'string|nullable|confirmed|max:191'],
                    'password_confirmation' => ['password' => 'string|nullable'],
                ],
                'width' => ['1-2@m']
            ]
        ];
    }
}

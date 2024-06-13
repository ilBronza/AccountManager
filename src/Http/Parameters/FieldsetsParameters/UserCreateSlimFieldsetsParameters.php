<?php

namespace IlBronza\AccountManager\Http\Parameters\FieldsetsParameters;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;

class UserCreateSlimFieldsetsParameters extends FieldsetParametersFile
{
    public function _getFieldsetsParameters() : array
    {
        return [
            'base' => [
                'translationPrefix' => 'accountmanager::fields',
                'fields' => [
                    'new_first_name' => ['text' => 'string|required|max:191'],
                    'new_surname' => ['text' => 'string|required|max:191'],
                    'new_email' => ['email' => 'email|required|max:191|unique:users,email'],
                ],
                'width' => ['1-2@m']
            ],
        ];
    }
}

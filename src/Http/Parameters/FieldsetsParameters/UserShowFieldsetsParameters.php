<?php

namespace IlBronza\AccountManager\Http\Parameters\FieldsetsParameters;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;

class UserShowFieldsetsParameters extends FieldsetParametersFile
{
    public function _getFieldsetsParameters() : array
    {
        return [
            'base' => [
                'view' => [
                    'name' => 'accountmanager::userdata._avatar',
                ],
                'translationPrefix' => 'accountmanager',
                'fields' => [
                    'name' => ['text' => 'string|required|max:191'],
                    'email' => ['email' => 'email|required|max:191'],
                ],
                'width' => ['1-2@m']
            ],
            'userdata' => [
                'translationPrefix' => 'accountmanager',
                'fields' => [
                    'first_name' => ['text' => 'string|required|max:64'],
                    'surname' => ['text' => 'string|required|max:64'],

                    'fiscal_code' => ['text' => 'string|nullable|max:16'],
                    'birth_date' => ['date' => 'date|nullable'],
                    'gender' => ['select' => 'string|nullable'],
                ],
                'width' => ['1-2@m']
            ]
        ];
    }
}

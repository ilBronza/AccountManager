<?php

namespace IlBronza\AccountManager\Http\Parameters\FieldsetsParameters;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;

class UserdataEditFieldsetsParameters extends FieldsetParametersFile
{
    public function _getFieldsetsParameters() : array
    {
        return [
            'base' => [
                'translationPrefix' => 'accountmanager::accountmanager',
                'fields' => [
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
                ],
                'width' => ['1-2@m']
            ]
        ];
    }
}

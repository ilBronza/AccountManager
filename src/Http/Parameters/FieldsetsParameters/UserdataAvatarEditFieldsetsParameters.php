<?php

namespace IlBronza\AccountManager\Http\Parameters\FieldsetsParameters;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;

class UserdataAvatarEditFieldsetsParameters extends FieldsetParametersFile
{
    public function _getFieldsetsParameters() : array
    {
        return [
            'base' => [
                'translationPrefix' => 'accountmanager::fields',
                'fields' => [
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

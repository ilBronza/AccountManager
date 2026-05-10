<?php

namespace IlBronza\AccountManager\Http\Parameters\TableFields;

use IlBronza\Datatables\Providers\FieldsGroupParametersFile;

class UserAccessLogTableFieldsParameters extends FieldsGroupParametersFile
{
    public static function getFieldsGroup(): array
    {
        return [
            'translationPrefix' => 'accountmanager::fields',
            'fields' => [
                'visited_at' => [
                    'type' => 'dates.date',
                    'filterRange' => true,
                ],
                'user_name' => [
                    'type' => 'flat',
                    'filterRange' => 'alphabetical',
                ],
                'ip' => [
                    'type' => 'flat',
                    'filterRange' => 'alphabetical',
                ],
                'method' => [
                    'type' => 'flat',
                    'filterRange' => 'alphabetical',
                ],
                'url' => 'flat',
            ],
        ];
    }
}

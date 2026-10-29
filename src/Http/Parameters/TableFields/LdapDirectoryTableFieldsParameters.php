<?php

namespace IlBronza\AccountManager\Http\Parameters\TableFields;

use IlBronza\Datatables\Providers\FieldsGroupParametersFile;

class LdapDirectoryTableFieldsParameters extends FieldsGroupParametersFile
{
    public static function getFieldsGroup(): array
    {
        $fields = [
            'id' => ['type' => 'flat', 'translatedName' => 'ID', 'visible' => false, 'rowId' => true, 'filterable' => false],
        ];
        foreach (['directory_user', 'state', 'local_user', 'association'] as $cell) {
            $fields[$cell] = [
                'type' => 'accountmanager::ldapDirectoryCell',
                'cell' => $cell,
                'datatableType' => 'html',
                'width' => match ($cell) {
                    'directory_user' => '22em',
                    'state' => '14em',
                    'local_user' => '24em',
                    'association' => '30em',
                },
            ];
        }
        $fields['directory_user']['order'] = ['type' => 'asc'];
        $fields['association'] += ['sortable' => false, 'filterable' => false, 'filterType' => 'none'];

        return ['translationPrefix' => 'accountmanager::directory', 'fields' => $fields];
    }
}

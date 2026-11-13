<?php

namespace IlBronza\AccountManager\Http\Parameters\TableFields;

use IlBronza\Datatables\Providers\FieldsGroupParametersFile;

class RouteTableFieldsParameters extends FieldsGroupParametersFile
{
    public static function getFieldsGroup(): array
    {
        return [
            'translationPrefix' => 'accountmanager::route-access',
            'fields' => [
                'id' => [
                    'type' => 'flat',
                    'translatedName' => 'ID',
                    'visible' => false,
                    'rowId' => true,
                    'filterable' => false,
                ],
                'row_parent_id' => [
                    'type' => 'ibDtV2.rowParentId',
                    'flatOnOrder' => true,
                    'order' => ['type' => 'asc'],
                ],
                'selection' => ['type' => 'flat', 'sortable' => false],
                'path' => [
                    'type' => 'flat',
                    'datatableType' => 'html',
                ],
                'name' => 'flat',
                'translated_name' => 'flat',
                'description' => 'flat',
                'system_roles' => 'flat',
                'assignments' => 'flat',
                'preview' => [
                    'type' => 'links.link',
                    'requireElement' => true,
                    'function' => 'getPreviewUrl',
                    'textMethod' => 'getPreviewLabel',
                    'htmlClasses' => ['uk-button', 'uk-button-default', 'uk-button-small'],
                    'dataAttributes' => ['route-preview' => true],
                    'width' => 'auto',
                    'sortable' => false,
                    'filterable' => false,
                ],
            ],
        ];
    }
}

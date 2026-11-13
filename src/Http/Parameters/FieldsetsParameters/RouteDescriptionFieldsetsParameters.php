<?php

namespace IlBronza\AccountManager\Http\Parameters\FieldsetsParameters;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;

class RouteDescriptionFieldsetsParameters extends FieldsetParametersFile
{
    public function _getFieldsetsParameters(): array
    {
        return ['base' => [
            'translationPrefix' => 'accountmanager::route-access',
            'fields' => ['description' => ['text' => 'present|nullable|string|max:10000']],
        ]];
    }
}

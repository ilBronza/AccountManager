<?php

namespace IlBronza\AccountManager\Http\Controllers\Users;

class TrashedIndexUserController extends IndexUserController
{
    public function setModelClass()
    {
        $this->modelClass = config("accountmanager.models.user.class");
    }

    public function getIndexElements()
    {
        return $this->getModelClass()::withoutGlobalScope(ActiveScope::class)->onlyTrashed()->with('roles', 'permissions')->get();
    }

    public function getIndexFieldsArray()
    {
        $result = config('accountmanager.models.user.fieldsGroupsFiles.index')::getTracedFieldsGroup();

        unset($result['fields']['mySelfDelete']);
        unset($result['fields']['mySelfEdit']);
        unset($result['fields']['mySelfDuplicate']);
        unset($result['fields']['mySelfEditUserdata']);

        $result['fields']['mySelfUnDelete'] = 'links.unDelete';

        return $result;
    }
}

<?php

namespace IlBronza\AccountManager\Http\Controllers\AccessLogs;

use IlBronza\AccountManager\Models\UserAccessLog;
use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Traits\CRUDIndexTrait;
use IlBronza\CRUD\Traits\CRUDPlainIndexTrait;

use function config;

class IndexUserAccessLogController extends CRUD
{
    use CRUDPlainIndexTrait;
    use CRUDIndexTrait;

    static $packageConfigPrefix = 'accountmanager';

    public $avoidCreateButton = true;

    public $allowedMethods = ['index'];

    public function setModelClass()
    {
        $this->modelClass = config('accountmanager.models.accessLog.class', UserAccessLog::class);
    }

    public function getIndexElements()
    {
        return $this->getModelClass()::query()
            ->with('user')
            ->orderByDesc('visited_at')
            ->get();
    }

    public function getIndexFieldsArray()
    {
        return config('accountmanager.models.accessLog.fieldsGroupsFiles.index')::getTracedFieldsGroup();
    }
}

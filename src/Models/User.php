<?php

namespace IlBronza\AccountManager\Models;

use App\Models\User as BaseUser;
use IlBronza\AccountManager\Traits\AccountManagerUserPermissionsTrait;
use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDRelationshipModelTrait;

class User extends BaseUser
{
    use AccountManagerUserPermissionsTrait;

    use CRUDModelTrait;
    use CRUDRelationshipModelTrait;
}

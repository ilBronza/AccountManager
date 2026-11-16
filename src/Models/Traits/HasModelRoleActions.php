<?php

namespace IlBronza\AccountManager\Models\Traits;

use IlBronza\AccountManager\Authorization\ModelAccess;
use IlBronza\AccountManager\Models\ModelRoleAction;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasModelRoleActions
{
    public function modelRoleActions(): MorphMany
    {
        return $this->morphMany(ModelRoleAction::class, 'model');
    }

    /** null inherits, [] denies everyone, otherwise the array contains allowed role IDs. */
    public function setActionRoles(string $action, ?array $roles): void
    {
        app(ModelAccess::class)->replace($this, $action, $roles);
        $this->unsetRelation('modelRoleActions');
    }
}

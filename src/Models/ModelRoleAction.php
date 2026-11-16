<?php

namespace IlBronza\AccountManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModelRoleAction extends Model
{
    protected $table = 'accountmanager_model_role_actions';

    protected $guarded = [
        '*',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(config('permission.models.role', Role::class));
    }
}

<?php

namespace IlBronza\AccountManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAccessLog extends Model
{
    protected $table = 'user_access_logs';

    public $timestamps = false;

    protected $appends = ['user_name'];

    protected $fillable = [
        'user_id',
        'visited_at',
        'method',
        'url',
        'ip',
        'user_agent',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        $this->connection = config('accountmanager.logUserAccess.connection', 'activityMysql');
        parent::__construct($attributes);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('accountmanager.models.user.class'));
    }

    public function getUserNameAttribute(): ?string
    {
        if ($this->relationLoaded('user') && $this->user) {
            if (method_exists($this->user, 'getFullName')) {
                return $this->user->getFullName();
            }

            return $this->user->name ?? null;
        }

        return null;
    }
}

<?php

namespace IlBronza\AccountManager\Models;

use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use ReflectionMethod;

class Route extends Model
{
    use CRUDModelTrait;

    public static $packageConfigPrefix = 'accountmanager';
    public static $modelConfigPrefix = 'route';

    protected $table = 'accountmanager_routes';
    protected $fillable = ['name', 'signature', 'domain', 'uri', 'methods', 'action', 'description', 'system_roles', 'is_registered'];
    protected $casts = ['methods' => 'array', 'system_roles' => 'array', 'is_registered' => 'boolean'];

    public function hasMissingControllerAction(): bool
    {
        if ($this->action === 'Closure') return false;

        [$class, $method] = Str::parseCallback($this->action, '__invoke');
        if (! class_exists($class)) return true;
        if (method_exists($class, $method)) return ! (new ReflectionMethod($class, $method))->isPublic();

        // Laravel's base __call throws BadMethodCallException for an absent action.
        return ! method_exists($class, '__call')
            || (new ReflectionMethod($class, '__call'))->getDeclaringClass()->getName() === Controller::class;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(config('permission.models.role'), 'accountmanager_route_role', 'route_id', 'role_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(config('permission.models.permission'), 'accountmanager_route_permission', 'route_id', 'permission_id');
    }

    public function getRouteBaseNamePrefix(): ?string
    {
        return config('accountmanager.routePrefix');
    }

    public function getPluralTranslatedClassname()
    {
        return __('accountmanager::route-access.title');
    }
}

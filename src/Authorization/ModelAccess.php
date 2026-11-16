<?php

namespace IlBronza\AccountManager\Authorization;

use IlBronza\AccountManager\Models\ModelRoleAction;
use IlBronza\AccountManager\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/** Local restrictions only. Consumers supply their own baseline and hierarchy. */
class ModelAccess
{
    public function query(Model $model): Builder
    {
        $rule = new ModelRoleAction;
        $rule->setConnection($model->getConnectionName());

        return $rule->newQuery()->where('model_type', $model->getMorphClass())
            ->where('model_id', (string) $model->getKey());
    }

    public function roles(Model $model, string $action): ?array
    {
        // Query afresh: a revocation must apply even to previously loaded models.
        $roles = $this->query($model)->where('action', $action)->pluck('role_id');
        if ($roles->isEmpty())
            return null;
        if ($roles->containsStrict(null))
            return [];

        return $roles->map(fn ($id) => (string) $id)->unique()->values()->all();
    }

    public function allows(Model $model, string $action, ?object $user): bool
    {
        return $this->allowsAll([
            $model,
        ], [
            $action,
        ], $user);
    }

    /** Every configured model/action restriction must pass; missing rows inherit. */
    public function allowsAll(iterable $models, array $actions, ?object $user): bool
    {
        $connections = [];
        foreach ($models as $model)
            $connections[$model->getConnection()->getName()][] = $model;
        foreach ($connections as $models) {
            $rule = new ModelRoleAction;
            $rule->setConnection($models[0]->getConnectionName());
            $rules = $rule->newQuery()->whereIn('action', $actions)->where(function ($query) use ($models): void {
                foreach ($models as $model)
                    $query->orWhere(function ($query) use ($model): void {
                        $query->where('model_type', $model->getMorphClass())->where('model_id', (string) $model->getKey());
                    });
            })->get();
            if ($rules->isEmpty())
                continue;
            if ($rules->contains(fn ($rule) => $rule->role_id === null) || ! $user || ! method_exists($user, 'roles'))
                return false;
            // Use fresh memberships, not a possibly stale Spatie relation cache.
            $roleIds = $user->roles()->pluck($user->roles()->getRelated()->getQualifiedKeyName())->map('strval')->all();
            foreach ($rules->groupBy(fn ($rule) => json_encode([
                $rule->model_type,
                $rule->model_id,
                $rule->action,
            ])) as $group)
                if (array_intersect($roleIds, $group->pluck('role_id')->map('strval')->all()) === [])
                    return false;
        }

        return true;
    }

    public function replace(Model $model, string $action, ?array $roles): void
    {
        $this->replaceMany($model, [
            $action => $roles,
        ]);
    }

    /** Atomically replaces all provided actions while leaving other actions untouched. */
    public function replaceMany(Model $model, array $actions): void
    {
        if (! $model->exists)
            throw new InvalidArgumentException('Save the resource before assigning access rules.');
        foreach ($actions as $action => $roles) {
            if (! is_string($action) || ! preg_match('/^[a-zA-Z][a-zA-Z0-9_.:-]{0,63}$/D', $action))
                throw new InvalidArgumentException('Invalid access action.');
            if ($roles !== null && (! is_array($roles) || count(array_filter($roles, fn ($id) => ! is_int($id) && ! is_string($id)))))
                throw new InvalidArgumentException('Role IDs must be strings or integers.');
        }

        $model->getConnection()->transaction(function () use ($model, $actions): void {
            // Also serializes the first assignment, when no pivot rows exist yet.
            $model->newQuery()->whereKey($model->getKey())->lockForUpdate()->firstOrFail();
            $roleClass = config('permission.models.role', Role::class);
            foreach ($actions as $action => $roles) {
                if ($roles !== null) {
                    $roles = array_values(array_unique(array_map('strval', $roles)));
                    $role = new $roleClass;
                    $role->setConnection($model->getConnectionName());
                    if ($role->newQuery()->whereKey($roles)->count() !== count($roles))
                        throw ValidationException::withMessages([
                            'roles' => 'Unknown or deleted role.',
                        ]);
                }
                $this->query($model)->where('action', $action)->delete();
                if ($roles === null)
                    continue;
                foreach ($roles === [] ? [
                    null,
                ] : $roles as $roleId) {
                    $rule = new ModelRoleAction;
                    $rule->setConnection($model->getConnectionName());
                    $rule->forceFill([
                        'model_type' => $model->getMorphClass(),
                        'model_id' => (string) $model->getKey(),
                        'action' => $action,
                        'role_id' => $roleId,
                    ])->save();
                }
            }
        });
        $model->unsetRelation('modelRoleActions');
    }
}

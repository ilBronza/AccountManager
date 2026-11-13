<?php

namespace IlBronza\AccountManager\Routing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Routing\Route;
use Illuminate\Support\Reflector;
use Illuminate\Support\Str;

class RouteExampleParameters
{
    private const SAMPLE_SIZE = 12;
    private array $values = [];
    private array $classes = [];
    private array $typed = [];

    private function __construct(private Route $route) {}

    /** Identify parameter sources without querying the tables behind the routes. */
    public static function forRoute(Route $route): ?self
    {
        $parameters = new self($route);
        if (! $route->parameterNames()) return $parameters;

        if ($route->getDomain()) {
            preg_match($route->toSymfonyRoute()->compile()->getHostRegex(), request()->getHost(), $matches);
            $parameters->values = array_intersect_key($matches, array_flip($route->parameterNames()));
        }

        $defaults = $route->defaults + url()->getDefaultParameters();
        foreach ($route->signatureParameters(['subClass' => Model::class]) as $argument) {
            $class = Reflector::getParameterClassName($argument);
            $parameters->typed[$argument->getName()] = $parameters->typed[Str::snake($argument->getName())] = $class;
        }

        foreach ($route->parameterNames() as $name) {
            if (isset($parameters->values[$name])) continue;
            if (isset($defaults[$name])) {
                $parameters->values[$name] = $defaults[$name];
                continue;
            }
            if (str_contains($route->uri(), '{'.$name.'?}')) continue;

            $class = $parameters->typed[$name] ?? self::modelFor($name, $route->uri());
            if (! $class) return null;
            $parameters->classes[$name] = $class;
        }

        return $parameters;
    }

    public function samples(): iterable
    {
        $paths = $this->relationshipPaths();
        if ($paths === null) return;

        $remaining = self::SAMPLE_SIZE * max(1, count($this->classes));
        yield from $this->sampleNext(array_keys($this->classes), $paths, $this->values, null, $remaining);
    }

    private function sampleNext(array $names, array $paths, array $values, ?Relation $relation, int &$remaining): iterable
    {
        $name = array_shift($names);
        if ($name === null) {
            yield $values;
            return;
        }

        $class = $this->classes[$name];
        $model = new $class;
        $query = $relation ?? $model->newQuery();
        $field = $this->route->bindingFieldFor($name) ?? (isset($this->typed[$name]) ? $model->getRouteKeyName() : $model->getKeyName());
        if ($paths[$name] !== '') $query->has($paths[$name]);
        $query->whereNotNull($model->qualifyColumn($field));

        // A small sample also covers reserved slugs and route constraints.
        // For intermediate parameters the relationship query must use this actual parent.
        $childRelation = Str::before($paths[$name], '.');
        foreach ($query->limit(min(self::SAMPLE_SIZE, $remaining))->get() as $record) {
            if ($remaining <= 0) return;
            $remaining--;
            $key = (string) $record->getAttribute($field);
            if ($key === '' || in_array($key, ['.', '..'], true) || strpbrk($key, '/\\?#%') !== false) continue;
            $values[$name] = $key;
            $children = $childRelation ? $record->{$childRelation}() : null;
            yield from $this->sampleNext($names, $paths, $values, $children, $remaining);
        }
    }

    /** Use Eloquent's child-binding convention and select parents that have matching children. */
    private function relationshipPaths(): ?array
    {
        $paths = [];
        $child = null;
        foreach (array_reverse($this->classes, true) as $name => $class) {
            $paths[$name] = '';
            if ($child !== null) {
                $relation = Str::plural(Str::camel($child));
                if (method_exists($class, $relation)) {
                    $link = (new $class)->$relation();
                    if (! $link instanceof Relation || ! is_a($link->getRelated(), $this->classes[$child])) return null;
                    $paths[$name] = rtrim($relation.'.'.$paths[$child], '.');
                } elseif (! $this->route->preventsScopedBindings() && ($this->route->enforcesScopedBindings() || $this->route->bindingFieldFor($child))) {
                    return null;
                }
            }
            $child = $name;
        }

        return $paths;
    }

    private static function modelFor(string $parameter, string $uri): ?string
    {
        $name = preg_replace('/(_id|Id)$/', '', $parameter);
        if ($parameter === 'id') {
            $name = Str::singular(Str::afterLast(rtrim(Str::before($uri, '{id}'), '/'), '/'));
        }
        $class = Relation::getMorphedModel($name) ?? Relation::getMorphedModel(Str::studly($name))
            ?? config('accountmanager.models.'.Str::camel($name).'.class') ?? 'App\\Models\\'.Str::studly($name);

        return is_subclass_of($class, Model::class) ? $class : null;
    }
}

<?php

namespace IlBronza\AccountManager\Routing;

use Illuminate\Support\Fluent;

/** Presentation row for a virtual URI branch or a catalog route; never persisted. */
class RouteTreeRow extends Fluent
{
    public function getKey(): string
    {
        return (string) $this->id;
    }

    public function getPreviewUrl(): ?string
    {
        if (! $this->route || ! app(RoutePreview::class)->canPreview($this->route)) {
            return null;
        }

        return route(config('accountmanager.routePrefix').'routes.preview', ['route' => $this->route->getKey()]);
    }

    public function getPreviewLabel(): string
    {
        return __('accountmanager::route-access.preview');
    }

    public function getRowParentIdAttribute(): array
    {
        $parent = ['value' => $this->parent_id, 'row_opened' => $this->parent_id === null];
        if ($this->route === null) $parent['row_group'] = true;

        return $parent;
    }

    public function __get($key)
    {
        return $key === 'row_parent_id' ? $this->getRowParentIdAttribute() : parent::__get($key);
    }

    public function offsetExists($offset): bool
    {
        return $offset === 'row_parent_id' || parent::offsetExists($offset);
    }

    public function offsetGet($offset): mixed
    {
        return $offset === 'row_parent_id' ? $this->getRowParentIdAttribute() : parent::offsetGet($offset);
    }
}

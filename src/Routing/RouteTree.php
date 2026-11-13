<?php

namespace IlBronza\AccountManager\Routing;

use Illuminate\Support\Collection;

class RouteTree
{
    /** URI segments form branches; named routes are leaves, including HTTP method variants. */
    public function rows(Collection $routes): Collection
    {
        $domains = [];
        $access = app(RouteAccess::class);
        $routeNames = array_fill_keys($routes->pluck('name')->filter()->all(), true);
        foreach ($routes as $route) {
            if ($access->isExcluded($route->name, $route->uri)) continue;
            $formName = $access->correspondingFormRouteName($route->name);
            if ($formName !== null && isset($routeNames[$formName])) continue;

            $domain = $route->domain ?? '';
            if (! isset($domains[$domain])) $domains[$domain] = $this->branch($domain, '', $domain ?: '/', 0);
            $node = &$domains[$domain];
            $path = '';
            foreach (array_filter(explode('/', trim($route->uri, '/')), fn ($segment) => $segment !== '') as $segment) {
                $path .= '/'.$segment;
                if (! isset($node['children'][$segment])) {
                    $node['children'][$segment] = $this->branch($domain, $path, $segment, $node['depth'] + 1);
                }
                $node = &$node['children'][$segment];
            }
            $node['routes'][] = $route;
            unset($node);
        }
        ksort($domains, SORT_NATURAL);
        $rows = collect();
        foreach ($domains as $domain) $this->flatten($domain, $rows);

        return $rows;
    }

    private function branch(string $domain, string $path, string $label, int $depth): array
    {
        return ['id' => 'branch-'.hash('sha256', $domain.'|'.$path), 'label' => $label,
            'depth' => $depth, 'children' => [], 'routes' => []];
    }

    private function flatten(array $node, Collection $rows, ?string $parentId = null): array
    {
        $row = new RouteTreeRow(['id' => $node['id'], 'label' => $node['label'], 'depth' => $node['depth'],
            'parent_id' => $parentId, 'route_ids' => [], 'route' => null, 'excluded' => false]);
        $rows->push($row);
        usort($node['routes'], fn ($a, $b) => strcmp($a->name ?? '', $b->name ?? ''));
        foreach ($node['routes'] as $route) {
            $excluded = app(RouteAccess::class)->isExcluded($route->name, $route->uri);
            $ids = $route->name !== null && ! $excluded ? [$route->getKey()] : [];
            $row->route_ids = array_merge($row->route_ids, $ids);
            $rows->push(new RouteTreeRow(['id' => 'route-'.$route->getKey(), 'label' => implode('|', $route->methods),
                'parent_id' => $row->id, 'depth' => $row->depth + 1,
                'route_ids' => $ids, 'route' => $route, 'excluded' => $excluded]));
        }
        ksort($node['children'], SORT_NATURAL);
        foreach ($node['children'] as $child) {
            $row->route_ids = array_merge($row->route_ids, $this->flatten($child, $rows, $row->id));
        }

        return $row->route_ids;
    }
}

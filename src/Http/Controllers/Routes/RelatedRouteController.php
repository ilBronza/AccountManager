<?php

namespace IlBronza\AccountManager\Http\Controllers\Routes;

use IlBronza\AccountManager\Helpers\RouteSyncHelper;
use IlBronza\AccountManager\Routing\RelatedRouteScope;
use IlBronza\AccountManager\Routing\RouteAccess;
use Illuminate\Http\Request;

class RelatedRouteController extends RouteController
{
    public $allowedMethods = ['index'];
    private RelatedRouteScope $related;

    public function index(Request $request)
    {
        $input = $request->validate([
            'related_to' => ['required', 'string', 'max:255'],
            'scope' => ['nullable', 'string', 'max:2048'],
        ]);
        RouteSyncHelper::sync();
        $this->related = RelatedRouteScope::resolve($input['related_to'], $input['scope'] ?? null);

        return $this->_index($request, 'related-routes-'.substr(hash('sha256', $this->related->anchor->name.'|'.$this->related->scope), 0, 16));
    }

    public function beforeIndex(): void
    {
        // Synced before resolving the scope, including for DataTables AJAX requests.
    }

    public function getIndexElements()
    {
        return $this->getRouteTableRows($this->related->routes());
    }

    public function beforeRenderIndex()
    {
        $this->setPageTitle(__('accountmanager::route-access.related_title'));
        $this->table->setCaption('');
        view()->share('ib_IFRAMED', request()->boolean('iframed'));
        $this->table->setUrl(route(config('accountmanager.routePrefix').'routes.related', [
            'related_to' => $this->related->anchor->name, 'scope' => $this->related->scope, 'iframed' => 'true',
        ]));
        $this->table->addTopView('accountmanager::routes.related-controls', ['related' => $this->related]);
        // Put the shared bulk controls below the table so the popup opens on the routes.
        $this->table->addBottomView('accountmanager::routes.controls', [
            'roles' => app(RouteAccess::class)->assignableRolesQuery()->orderBy('name')->get(),
            'permissions' => app(config('permission.models.permission'))->newQuery()->orderBy('name')->get(),
            'compact' => true,
        ]);
        $this->table->addBottomView('accountmanager::routes.scripts', ['tableId' => $this->table->getId()]);
        $this->table->addBottomView('accountmanager::routes.related-context', ['related' => $this->related]);
    }
}

<?php

namespace IlBronza\AccountManager\Http\Controllers\Routes;

use IlBronza\AccountManager\Helpers\RouteSyncHelper;
use IlBronza\AccountManager\Http\Parameters\FieldsetsParameters\RouteDescriptionFieldsetsParameters;
use IlBronza\AccountManager\Http\Parameters\TableFields\RouteTableFieldsParameters;
use IlBronza\AccountManager\Models\Route;
use IlBronza\AccountManager\Routing\RouteAccess;
use IlBronza\AccountManager\Routing\RouteNameTranslations;
use IlBronza\AccountManager\Routing\RouteTree;
use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Traits\CRUDIndexTrait;
use IlBronza\CRUD\Traits\CRUDPlainIndexTrait;
use IlBronza\CRUD\Traits\CRUDUpdateEditorTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RouteController extends CRUD
{
    use CRUDPlainIndexTrait;
    use CRUDIndexTrait;
    use CRUDUpdateEditorTrait;

    public static $packageConfigPrefix = 'accountmanager';
    public $allowedMethods = ['index', 'update'];
    public $avoidCreateButton = true;

    public function setModelClass()
    {
        $this->modelClass = config('accountmanager.models.route.class', Route::class);
    }

    public function beforeIndex(): void
    {
        RouteSyncHelper::sync();
    }

    public function getIndexElements()
    {
        $routes = $this->getModelClass()::with(['roles', 'permissions'])->where('is_registered', true)->get();

        return $this->getRouteTableRows($routes);
    }

    protected function getRouteTableRows(Collection $routes): Collection
    {
        $entries = app(RouteTree::class)->rows($routes);
        $translations = app(RouteNameTranslations::class);

        foreach ($entries as $entry) {
            $entry->selection = $this->renderRouteCell($entry, 'selection');
            $entry->path = $this->renderRouteCell($entry, 'path');
            $entry->name = $this->renderRouteCell($entry, 'name');
            $entry->translated_name = $entry->route
                ? e($translations->forName($entry->route->name))
                : '';
            $entry->description = $this->renderRouteCell($entry, 'description');
            $entry->system_roles = $this->renderRouteCell($entry, 'system_roles');
            $entry->assignments = $this->renderRouteCell($entry, 'assignments');
        }

        return $entries;
    }

    private function renderRouteCell(object $entry, string $cell): string
    {
        return view('accountmanager::routes.cell', [
            'entry' => $entry,
            'cell' => $cell,
        ])->render();
    }

    public function getIndexFieldsArray()
    {
        return config('accountmanager.models.route.fieldsGroupsFiles.index', RouteTableFieldsParameters::class)::getTracedFieldsGroup();
    }

    public function getUpdateParametersClass(): RouteDescriptionFieldsetsParameters
    {
        return new RouteDescriptionFieldsetsParameters;
    }

    public function getFormFieldsets(string $type)
    {
        return $type === 'updateEditor'
            ? $this->getUpdateParametersClass()->getFieldsetsParameters()
            : parent::getFormFieldsets($type);
    }

    public function update(Request $request, string $route)
    {
        $model = $this->getModelClass()::query()->where('is_registered', true)->findOrFail($route);
        abort_if(app(RouteAccess::class)->isExcluded($model->name, $model->uri), 404);
        $request->validate([
            'field' => ['required', 'in:description'],
            'ibaction' => ['prohibited'], 'toggle' => ['prohibited'], 'ib-editor-batch' => ['prohibited'],
        ]);
        $this->setModel($model);

        // Tree cells contain rendered HTML; let DataTables reload their updated content.
        return array_merge($this->_updateEditor($request), ['ibaction' => true, 'action' => 'reloadTable']);
    }

    public function beforeRenderIndex()
    {
        $this->table->addTopView('accountmanager::routes.controls', [
            'roles' => app(RouteAccess::class)->assignableRolesQuery()->orderBy('name')->get(),
            'permissions' => app(config('permission.models.permission'))->newQuery()->orderBy('name')->get(),
        ]);
        $this->table->addBottomView('accountmanager::routes.scripts', ['tableId' => $this->table->getId()]);
    }
}

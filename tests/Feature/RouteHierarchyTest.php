<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Helpers\RouteSyncHelper;
use IlBronza\AccountManager\Models\Route as CatalogRoute;
use IlBronza\AccountManager\Routing\RouteTree;
use IlBronza\AccountManager\Routing\RouteTreeRow;
use IlBronza\AccountManager\Tests\TestCase;
use IlBronza\Datatables\DatatablesFields\FieldFactory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class RouteHierarchyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['000000_create_accountmanager_routes_tables', '000001_add_description_to_accountmanager_routes_table',
            '000002_add_system_roles_to_accountmanager_routes_table'] as $migration) {
            (require __DIR__.'/../../database/migrations/2026_11_13_'.$migration.'.php')->up();
        }
        config(['accountmanager.routeAccess.enabled' => true]);
        Route::middleware('web')->group(function () {
            Route::get('hierarchy-clients', fn () => 'index')->name('hierarchyClients.index');
            Route::post('hierarchy-clients', fn () => 'store')->name('hierarchyClients.store');
            Route::get('hierarchy-clients/{client}/notes', fn () => 'notes')->name('hierarchyClients.notes.index');
            Route::get('missing-action', ['App\\MissingController', 'index'])->name('hierarchyMissing.index');
        });
        Route::domain('admin.example.test')->get('hierarchy-clients', fn () => 'admin')->name('hierarchyAdmin.index');
        $this->actingAs($this->makeUserWithRole('administrator'));
    }

    public function test_virtual_rows_expose_the_existing_uri_tree_through_the_v2_getter_without_schema_changes(): void
    {
        RouteSyncHelper::sync();
        $rows = app(RouteTree::class)->rows(CatalogRoute::where('is_registered', true)->get());
        $indexed = $rows->keyBy('id');
        $field = FieldFactory::createByType('row_parent_id', 'ibDtV2.rowParentId', [], 1);
        $this->assertFalse(Schema::hasColumn('accountmanager_routes', 'parent_id'));
        $this->assertCount($rows->count(), $indexed);
        foreach ($rows as $row) {
            $this->assertInstanceOf(RouteTreeRow::class, $row);
            $this->assertSame($row->id, $row->getKey());
            $this->assertSame($row->row_parent_id, data_get($row, 'row_parent_id'));
            $expected = ['value' => $row->parent_id, 'row_opened' => $row->depth === 0];
            if (! $row->route) $expected['row_group'] = true;
            $this->assertSame($expected, $field->transformValue($row));
            if ($row->parent_id === null) {
                $this->assertSame(0, $row->depth);
                continue;
            }
            $parent = $indexed->get($row->parent_id);
            $this->assertNotNull($parent);
            $this->assertNull($parent->route);
            $this->assertSame($parent->depth + 1, $row->depth);
            foreach ($row->route_ids as $id) $this->assertContains($id, $parent->route_ids);
        }
        $local = $rows->first(fn ($row) => $row->route?->name === 'hierarchyClients.index');
        $write = $rows->first(fn ($row) => $row->route?->name === 'hierarchyClients.store');
        $remote = $rows->first(fn ($row) => $row->route?->name === 'hierarchyAdmin.index');
        $this->assertSame($local->parent_id, $write->parent_id);
        $this->assertNotSame($local->id, $write->id);
        $this->assertNotSame($local->parent_id, $remote->parent_id);
    }

    public function test_route_index_activates_the_shared_field_and_returns_complete_parent_references(): void
    {
        $this->get('/account-management/routes')->assertOk()
            ->assertSee('data-field-name="row_parent_id"', false)->assertSee('ibDtRowParent:', false)
            ->assertSee('ibDtFlatOnOrder: true', false)->assertSee("name: 'ibDtHierarchy'", false)
            ->assertDontSee('data-field-name="position"', false);
        $rows = collect($this->getJson('/account-management/routes', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data'));
        $this->assertHierarchy($rows->all());
        $record = CatalogRoute::whereName('hierarchyClients.notes.index')->sole();
        $row = $rows->first(fn ($row) => $row[0] === 'route-'.$record->id);
        $missing = $rows->first(fn ($row) => str_contains($row[4], 'hierarchyMissing.index'));
        $this->assertStringContainsString(__('accountmanager::route-access.missing_action'), $missing[4]);
        $this->assertNull($missing[9][0]);
        $this->assertStringContainsString('GET|HEAD /hierarchy-clients/{client}/notes', $row[3]);
        $this->assertStringContainsString('hierarchyClients.notes.index', $row[4]);
        $this->assertStringContainsString('data-route-ids="['.$record->id.']"', $row[2]);
        $this->assertFalse($row[1]['row_opened']);

        $parents = $rows->mapWithKeys(fn ($row) => [$row[0] => $row[1]])->all();
        $this->putJson(route('accountmanagerroutes.update', ['route' => $record->id]), [
            'ib-editor' => true, 'field' => 'description', 'value' => 'Descrizione aggiornata nel ramo',
        ])->assertOk()->assertJson(['success' => true, 'action' => 'reloadTable']);
        $refreshed = collect($this->getJson('/account-management/routes', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data'));
        $this->assertSame($parents, $refreshed->mapWithKeys(fn ($row) => [$row[0] => $row[1]])->all());
        $updated = $refreshed->first(fn ($row) => $row[0] === 'route-'.$record->id);
        $this->assertStringContainsString('value="Descrizione aggiornata nel ramo"', $updated[6]);
        $this->assertSame($row[2], $updated[2]);
        $this->assertSame($row[9], $updated[9]);
    }

    public function test_unavailable_actions_distinguish_missing_methods_from_invokable_and_dynamic_controllers(): void
    {
        $this->assertTrue((new CatalogRoute(['action' => HierarchyPage::class.'@missing']))->hasMissingControllerAction());
        $this->assertTrue((new CatalogRoute(['action' => HierarchyPage::class.'@edit']))->hasMissingControllerAction());
        $this->assertFalse((new CatalogRoute(['action' => HierarchyPage::class]))->hasMissingControllerAction());
        $this->assertFalse((new CatalogRoute(['action' => HierarchyDynamicPage::class.'@page']))->hasMissingControllerAction());
    }

    public function test_related_route_table_reuses_the_same_hierarchy_with_all_ancestors_present(): void
    {
        $url = '/account-management/routes/related?'.http_build_query([
            'related_to' => 'hierarchyClients.notes.index', 'scope' => 'name:hierarchyClients.', 'iframed' => 'true',
        ]);
        $this->get($url)->assertOk()->assertSee('ibDtRowParent:', false);
        $rows = $this->getJson($url, ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->json('data');
        $this->assertHierarchy($rows);
    }

    private function assertHierarchy(array $rows): void
    {
        $ids = array_column($rows, 0);
        $this->assertNotEmpty($rows);
        $this->assertCount(count($ids), array_unique($ids));
        foreach ($rows as $row) {
            $this->assertSame(str_starts_with($row[0], 'branch-'), $row[1]['row_group'] ?? false);
            if ($row[1]['value'] === null) $this->assertTrue($row[1]['row_opened']);
            else {
                $this->assertContains($row[1]['value'], $ids);
                $this->assertFalse($row[1]['row_opened']);
            }
        }
    }
}

class HierarchyPage extends \Illuminate\Routing\Controller
{
    public function __invoke() {}
    protected function edit() {}
}

class HierarchyDynamicPage extends \Illuminate\Routing\Controller
{
    public function __call($method, $parameters) {}
}

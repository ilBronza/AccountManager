<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Helpers\RouteSyncHelper;
use IlBronza\AccountManager\Models\Permission;
use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Models\Route as CatalogRoute;
use IlBronza\AccountManager\Routing\RelatedRouteScope;
use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class RelatedRouteAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['000000_create_accountmanager_routes_tables', '000001_add_description_to_accountmanager_routes_table',
            '000002_add_system_roles_to_accountmanager_routes_table'] as $migration) {
            (require __DIR__.'/../../database/migrations/2026_11_13_'.$migration.'.php')->up();
        }
        config(['accountmanager.routeAccess.enabled' => true, 'accountmanager.routeAccess.exceptRoutes' => ['clients.secret']]);
        Route::middleware('web')->group(function () {
            Route::get('clients', fn () => view('uikittemplate::app'))->name('clients.index');
            Route::get('clients/create', fn () => 'create')->name('clients.create');
            Route::post('clients', fn () => 'store')->name('clients.store');
            Route::get('clients/{client}', fn () => 'show')->name('clients.show');
            Route::get('clients/{client}/edit', fn () => 'edit')->name('clients.edit');
            Route::put('clients/{client}', fn () => 'update')->name('clients.update');
            Route::get('clients/{client}/notes', fn () => 'notes')->name('clients.notes.index');
            Route::get('clients/custom', fn () => 'custom')->name('custom.index');
            Route::get('clients/secret', fn () => 'secret')->name('clients.secret');
            Route::get('clients-other', fn () => 'other')->name('clientsOther.index');
            Route::get('reports', fn () => 'reports')->name('reports.index');
            Route::get('/', fn () => 'home')->name('home');
        });
        Route::domain('admin.example.test')->middleware('web')->get('clients/admin', fn () => 'remote')->name('clients.remote');
    }

    private function url(string $name = 'clients.index', ?string $scope = null): string
    {
        return '/account-management/routes/related?'.http_build_query(array_filter([
            'related_to' => $name, 'scope' => $scope, 'iframed' => 'true',
        ], fn ($value) => $value !== null));
    }

    private function record(string $name): CatalogRoute
    {
        return CatalogRoute::whereName($name)->sole();
    }

    private function data(string $name = 'clients.index', ?string $scope = null): string
    {
        $response = $this->getJson($this->url($name, $scope), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);

        return collect($response->json('data'))->flatten()->implode('\n');
    }

    public function test_related_page_reuses_the_crud_datatable_and_preserves_context_in_its_ajax_url(): void
    {
        $this->actingAs($this->makeUserWithRole('routesManager'));
        $response = $this->get($this->url('clients.notes.index'))->assertOk()
            ->assertSee('wannabedatatable', false)->assertSee('id="related-route-scope"', false)
            ->assertSee('name="roles[]"', false)->assertSee('name="permissions[]"', false)
            ->assertSee('name:clients.notes.', false)->assertSee('name:clients.', false)
            ->assertDontSee('id="accountmanager-current-route-access"', false);
        preg_match('/data-url="([^"]+)"/', $response->getContent(), $matches);
        $this->assertStringContainsString('related_to=clients.notes.index', html_entity_decode($matches[1]));
        $this->assertStringContainsString('iframed=true', html_entity_decode($matches[1]));
        $this->assertNotNull($this->record('clients.notes.index'));
    }

    public function test_name_prefix_matches_siblings_and_nested_routes_with_segment_and_domain_boundaries(): void
    {
        $this->actingAs($this->makeUserWithRole('administrator'));
        $html = $this->data();
        foreach (['clients.index', 'clients.create', 'clients.edit', 'clients.show', 'clients.notes.index'] as $name) {
            $this->assertStringContainsString($name, $html);
        }
        foreach (['clientsOther.index', 'custom.index', 'reports.index', 'clients.remote', 'clients.secret', 'clients.store', 'clients.update'] as $name) {
            $this->assertStringNotContainsString($name, $html);
        }
        $this->assertStringContainsString(__('accountmanager::route-access.current_only'), $html);
    }

    public function test_scope_can_expand_to_parent_names_or_related_uri_segments(): void
    {
        $this->actingAs($this->makeUserWithRole('routesManager'));
        $narrow = $this->data('clients.notes.index');
        $this->assertStringContainsString('clients.notes.index', $narrow);
        $this->assertStringNotContainsString('clients.index', $narrow);
        $broad = $this->data('clients.notes.index', 'name:clients.');
        $this->assertStringContainsString('clients.index', $broad);
        $uri = $this->data('clients.index', 'uri:clients');
        $this->assertStringContainsString('custom.index', $uri);
        foreach (['clientsOther.index', 'reports.index', 'clients.remote'] as $name) {
            $this->assertStringNotContainsString($name, $uri);
        }
    }

    public function test_invalid_scopes_and_unavailable_anchor_pages_are_rejected(): void
    {
        $this->actingAs($this->makeUserWithRole('superadmin'));
        foreach (['name:client', 'name:reports.', 'uri:', 'uri:reports'] as $scope) {
            $this->getJson($this->url('clients.index', $scope))->assertUnprocessable()->assertJsonValidationErrors('scope');
        }
        foreach (['missing', 'clients.secret', 'clients.store'] as $name) {
            $this->getJson($this->url($name))->assertNotFound();
        }
        $this->data('home', 'current');
    }

    public function test_related_bulk_updates_stay_in_the_popup_context_and_apply_only_to_selected_routes(): void
    {
        $this->actingAs($this->makeUserWithRole('routesManager'));
        $this->get($this->url())->assertOk();
        $editor = Role::findOrCreate('editor', 'web');
        $permission = Permission::findOrCreate('clients.read', 'web');
        $ids = [$this->record('clients.index')->id, $this->record('clients.edit')->id];
        $payload = ['route_ids' => json_encode($ids), 'operation' => 'attach', 'roles' => [$editor->id],
            'permissions' => [$permission->id], 'related_to' => 'clients.index', 'scope' => 'name:clients.'];
        $redirect = route('accountmanagerroutes.related', ['related_to' => 'clients.index', 'scope' => 'name:clients.', 'iframed' => 'true']);
        $this->post('/account-management/routes/associate', $payload)->assertRedirect($redirect)
            ->assertSessionHas('routeAccessRelatedUpdated', 'clients.index');
        foreach ($ids as $id) {
            $this->assertDatabaseHas('accountmanager_route_role', ['route_id' => $id, 'role_id' => $editor->id]);
            $this->assertDatabaseHas('accountmanager_route_permission', ['route_id' => $id, 'permission_id' => $permission->id]);
        }
        $this->assertDatabaseCount('accountmanager_route_role', 2);
        $this->assertDatabaseCount('accountmanager_route_permission', 2);
        $this->get($redirect)->assertOk()->assertSee('accountmanager:related-routes-saved', false);
        $payload['operation'] = 'detach';
        $this->post('/account-management/routes/associate', $payload)->assertRedirect($redirect);
        $this->assertDatabaseCount('accountmanager_route_role', 0);
        $this->assertDatabaseCount('accountmanager_route_permission', 0);
    }

    public function test_related_bulk_validation_rejects_outside_or_excluded_routes_atomically(): void
    {
        $this->actingAs($this->makeUserWithRole('routesManager'));
        RouteSyncHelper::sync();
        $role = Role::findOrCreate('editor', 'web');
        foreach (['reports.index', 'clientsOther.index', 'clients.remote', 'clients.secret'] as $name) {
            $this->postJson('/account-management/routes/associate', [
                'route_ids' => json_encode([$this->record('clients.index')->id, $this->record($name)->id]),
                'operation' => 'attach', 'roles' => [$role->id], 'related_to' => 'clients.index', 'scope' => 'name:clients.',
            ])->assertUnprocessable();
        }
        $this->assertDatabaseCount('accountmanager_route_role', 0);
    }

    public function test_related_table_is_restricted_to_route_managers_and_feature_flag(): void
    {
        $this->getJson($this->url())->assertUnauthorized();
        $this->actingAs($this->makeUserWithRole('editor'));
        $this->getJson($this->url())->assertForbidden();
        $this->actingAs($this->makeUserWithRole('routesManager', 'manager@example.test'));
        config(['accountmanager.routeAccess.enabled' => false]);
        $this->getJson($this->url())->assertNotFound();
    }

    public function test_current_page_exposes_a_lazy_related_table_without_loading_it_in_readonly_pages(): void
    {
        $this->actingAs($this->makeUserWithRole('superadmin'));
        $this->get('/clients')->assertOk()->assertSee('Route collegate')->assertSee('data-related-frame', false);
        $this->getJson('/account-management/routes/access?route_name=clients.index')->assertOk()
            ->assertJsonPath('related_url', route('accountmanagerroutes.related', ['related_to' => 'clients.index', 'iframed' => 'true']));
        RouteSyncHelper::sync();
        $scope = RelatedRouteScope::resolve('home');
        $this->assertSame('current', $scope->scope);
        $this->assertSame(['home'], $scope->routes()->pluck('name')->all());
    }
}

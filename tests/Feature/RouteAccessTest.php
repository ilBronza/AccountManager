<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Models\Permission;
use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Models\Route as RouteRecord;
use IlBronza\AccountManager\Helpers\RouteSyncHelper;
use IlBronza\AccountManager\Routing\RouteTree;
use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RouteAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (require __DIR__.'/../../database/migrations/2026_11_13_000000_create_accountmanager_routes_tables.php')->up();
        (require __DIR__.'/../../database/migrations/2026_11_13_000001_add_description_to_accountmanager_routes_table.php')->up();
        (require __DIR__.'/../../database/migrations/2026_11_13_000002_add_system_roles_to_accountmanager_routes_table.php')->up();
        config(['accountmanager.routeAccess.enabled' => true]);
        Route::middleware('web')->group(function () {
            Route::get('clients', fn () => 'clients')->name('clients.index');
            Route::post('clients', fn () => 'stored')->name('clients.store');
            Route::get('clients/{client}', fn () => 'client')->name('clients.show');
            Route::get('clients/{client}/edit', fn () => 'edit')->name('clients.edit');
            Route::get('clients-other', fn () => 'other')->name('other');
            Route::get('unnamed-route', fn () => 'unnamed');
        });
    }

    private function sync(): void
    {
        RouteSyncHelper::sync();
    }

    private function record(string $name = 'clients.index'): RouteRecord
    {
        return RouteRecord::where('name', $name)->firstOrFail();
    }

    private function admin(string $role = 'superadmin'): void
    {
        $this->actingAs($this->makeUserWithRole($role));
    }

    private function association(array $ids, array $roles = [], array $permissions = [], string $operation = 'attach'): array
    {
        return ['route_ids' => json_encode($ids), 'roles' => $roles, 'permissions' => $permissions, 'operation' => $operation];
    }

    public function test_disabled_feature_leaves_existing_access_untouched_without_catalog_queries(): void
    {
        config(['accountmanager.routeAccess.enabled' => false]);
        Schema::drop('accountmanager_route_role');
        Schema::drop('accountmanager_route_permission');
        Schema::drop('accountmanager_routes');
        $this->get('/clients')->assertOk();
        $this->admin();
        $this->get('/account-management/routes')->assertNotFound();
        $this->post('/account-management/routes/sync')->assertNotFound();
        $this->post('/account-management/routes/associate')->assertNotFound();
        $this->assertFalse(Route::has('accountmanagerroutes.sync'));
    }

    public function test_unregistered_unassigned_and_unnamed_routes_are_denied(): void
    {
        $this->actingAs($this->makeUserWithRole('editor'));
        $this->get('/clients')->assertForbidden();
        $this->sync();
        $this->get('/clients')->assertForbidden();
        $this->get('/unnamed-route')->assertForbidden();
        $this->record()->roles()->attach(Role::findByName('editor'));
        $this->get('/clients')->assertOk();
        $this->record()->roles()->detach();
        $this->get('/clients')->assertForbidden();
    }

    public static function bypassRoles(): array
    {
        return [['superadmin'], ['superadministrator'], ['administrator']];
    }

    #[DataProvider('bypassRoles')]
    public function test_administrators_bypass_all_route_rules_and_can_manage_catalog(string $role): void
    {
        $this->admin($role);
        $this->get('/clients')->assertOk();
        $this->get('/unnamed-route')->assertOk();
        $this->get('/account-management/routes')->assertOk()->assertSee('accountmanager-route-associations', false);
        $this->post('/account-management/routes/sync')->assertNotFound();
        $this->get('/account-management/users')->assertOk();
    }

    public function test_any_associated_role_or_direct_or_inherited_permission_grants_access(): void
    {
        $this->sync();
        $editor = $this->makeUserWithRole('editor');
        $this->record()->roles()->attach(Role::findOrCreate('other-role', 'web'));
        $permission = Permission::findOrCreate('clients.read', 'web');
        $this->record()->permissions()->attach($permission);
        $this->actingAs($editor)->get('/clients')->assertForbidden();
        $editor->givePermissionTo($permission);
        $this->get('/clients')->assertOk();
        $editor->revokePermissionTo($permission);
        Role::findByName('editor')->givePermissionTo($permission);
        $editor->unsetRelation('roles');
        $this->get('/clients')->assertOk();
        $this->record()->permissions()->detach();
        $this->get('/clients')->assertForbidden();
        $this->record()->roles()->attach(Role::findByName('editor'));
        $this->get('/clients')->assertOk();
    }

    public function test_names_distinguish_same_uri_methods_and_do_not_inherit_parent_rules(): void
    {
        $this->sync();
        $this->actingAs($this->makeUserWithRole('editor'));
        $this->record()->roles()->attach(Role::findByName('editor'));
        $this->get('/clients')->assertOk();
        $this->post('/clients')->assertForbidden();
        $this->get('/clients/1')->assertForbidden();
        $this->get('/clients/1/edit')->assertForbidden();
        $this->get('/clients-other')->assertForbidden();
    }

    public function test_guest_cannot_access_assigned_route_and_authentication_exclusions_remain_reachable(): void
    {
        $this->sync();
        $this->record()->roles()->attach(Role::findOrCreate('editor', 'web'));
        $this->getJson('/clients')->assertUnauthorized();
        Route::middleware('web')->get('/test-login', fn () => 'login')->name('login');
        $this->get('/test-login')->assertOk();
        config(['accountmanager.routeAccess.exceptRoutes' => []]);
        $this->get('/test-login')->assertForbidden();
    }

    public function test_roles_and_permissions_with_same_names_on_other_guards_do_not_grant_access(): void
    {
        config(['auth.guards.api' => ['driver' => 'session', 'provider' => 'users']]);
        $this->sync();
        $this->actingAs($this->makeUserWithRole('editor'));
        $permission = Permission::findOrCreate('clients.read', 'web');
        Auth::user()->givePermissionTo($permission);
        $this->record()->roles()->attach(Role::findOrCreate('editor', 'api'));
        $this->record()->permissions()->attach(Permission::findOrCreate('clients.read', 'api'));
        $this->get('/clients')->assertForbidden();
    }

    public function test_api_authentication_runs_before_route_access_middleware(): void
    {
        $user = $this->makeUserWithRole('editor');
        config(['auth.guards.api' => ['driver' => 'route-access-test', 'provider' => 'users']]);
        Auth::viaRequest('route-access-test', fn ($request) => $request->bearerToken() === 'valid' ? $user : null);
        Route::middleware(['api', 'auth:api'])->get('/api/clients', fn () => 'api')->name('api.clients');
        $this->sync();
        $this->record('api.clients')->roles()->attach(Role::findByName('editor'));
        $this->getJson('/api/clients')->assertUnauthorized();
        Auth::forgetGuards();
        $this->withToken('valid')->getJson('/api/clients')->assertOk();
    }

    public function test_sync_preserves_associations_updates_metadata_and_retains_missing_records(): void
    {
        $this->sync();
        $record = $this->record();
        $record->roles()->attach(Role::findOrCreate('editor', 'web'));
        $record->permissions()->attach(Permission::findOrCreate('clients.read', 'web'));
        $router = new \Illuminate\Routing\Router(app('events'), app());
        $router->get('customers', fn () => 'ok')->name('clients.index');
        RouteSyncHelper::sync($router);
        $this->assertSame($record->id, $this->record()->id);
        $this->assertSame('customers', $this->record()->uri);
        $this->assertCount(1, $this->record()->roles);
        $this->assertCount(1, $this->record()->permissions);
        $this->assertFalse($this->record('clients.edit')->is_registered);
        $this->sync();
        $this->assertTrue($this->record('clients.edit')->is_registered);
        $this->assertSame('clients', $this->record()->uri);
        $this->assertSame(1, $this->record()->roles()->count());
    }

    public function test_sync_refuses_duplicate_names_without_changing_catalog(): void
    {
        $this->sync();
        $count = RouteRecord::count();
        $router = new \Illuminate\Routing\Router(app('events'), app());
        $router->get('one', fn () => 'ok')->name('duplicate');
        $router->get('two', fn () => 'ok')->name('duplicate');
        try {
            RouteSyncHelper::sync($router);
            $this->fail('Duplicate route names must be rejected.');
        } catch (HttpException $exception) {
            $this->assertStringContainsString('duplicate', $exception->getMessage());
        }
        $this->assertSame($count, RouteRecord::where('is_registered', true)->count());
    }

    public function test_tree_uses_segments_domains_and_synthetic_parents_and_includes_all_method_variants(): void
    {
        Route::middleware('web')->domain('admin.example.test')->get('clients', fn () => 'ok')->name('admin.clients');
        Route::middleware('web')->get('only/child/{id?}', fn () => 'ok')->name('only.child');
        $this->sync();
        $rows = app(RouteTree::class)->rows(RouteRecord::with(['roles', 'permissions'])->where('is_registered', true)->get());
        $clients = $rows->first(fn ($row) => $row->label === 'clients' && $row->depth === 1 && count($row->route_ids) === 4);
        $this->assertNotNull($clients);
        $this->assertEqualsCanonicalizing(RouteRecord::where('name', 'like', 'clients.%')->pluck('id')->all(), $clients->route_ids);
        $edit = $rows->first(fn ($row) => $row->route?->name === 'clients.edit');
        $this->assertContains($edit->route->id, $clients->route_ids);
        $this->assertGreaterThan($clients->depth, $edit->depth);
        $this->assertNotNull($rows->first(fn ($row) => $row->label === 'only' && ! $row->route));
        $admin = $rows->first(fn ($row) => $row->label === 'admin.example.test');
        $this->assertSame([$this->record('admin.clients')->id], $admin->route_ids);
    }

    public function test_management_page_and_datatables_render_tree_and_bulk_controls(): void
    {
        $this->sync();
        $this->admin();
        config(['datatables.pageLength' => 25, 'datatables.saveState' => true]);
        $this->get('/account-management/routes')->assertOk()->assertSee('wannabedatatable', false)
            ->assertSee('name="roles[]"', false)->assertSee('name="permissions[]"', false)
            ->assertSee('pageLength: 25', false)->assertSee('stateSave: true', false)
            ->assertDontSee('data-route-search', false)->assertDontSee('data-route-expand-all', false)
            ->assertDontSee('"paging"', false)->assertDontSee('"ordering"', false)->assertDontSee('"searching"', false);
        $data = $this->getJson('/account-management/routes', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonStructure(['data', 'recordsTotal', 'recordsFiltered']);
        $html = collect($data->json('data'))->flatten()->implode('\n');
        $this->assertStringContainsString('data-route-ids=', $html);
        $this->assertStringContainsString('clients.edit', $html);
        $this->assertStringContainsString(__('accountmanager::route-access.unnamed'), $html);
    }

    public function test_table_hides_route_and_uri_exclusions_without_leaving_empty_branches(): void
    {
        $this->admin();
        Route::get('clients/name-excluded/leaf', fn () => 'hidden')->name('private.tools');
        Route::get('clients/uri-excluded/leaf', fn () => 'hidden')->name('tools.reports');
        Route::domain('excluded.example.test')->get('uri-excluded/unnamed', fn () => 'hidden');
        $this->sync();
        config([
            'accountmanager.routeAccess.exceptRoutes' => ['private.*'],
            'accountmanager.routeAccess.exceptUris' => ['*uri-excluded*'],
        ]);

        $rows = app(RouteTree::class)->rows(RouteRecord::where('is_registered', true)->get());
        $clients = $rows->first(fn ($row) => $row->label === 'clients' && $row->depth === 1);
        $this->assertEqualsCanonicalizing(RouteRecord::where('name', 'like', 'clients.%')->pluck('id')->all(), $clients->route_ids);
        foreach (['name-excluded', 'uri-excluded', 'excluded.example.test'] as $label) {
            $this->assertFalse($rows->contains(fn ($row) => $row->label === $label));
        }

        $this->get('/account-management/routes')->assertOk();
        $data = $this->getJson('/account-management/routes', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $html = collect($data->json('data'))->flatten()->implode('\n');
        $this->assertStringContainsString('clients.index', $html);
        foreach (['private.tools', 'tools.reports', 'name-excluded', 'uri-excluded', 'excluded.example.test'] as $hidden) {
            $this->assertStringNotContainsString($hidden, $html);
        }
    }

    public function test_table_hides_write_routes_only_for_exact_corresponding_form_names(): void
    {
        $this->admin();
        Route::patch('note-types/{type}', fn () => 'updated')->name('notesmanagernotetypes.update');
        Route::post('destinations', fn () => 'stored')->name('clientsmanagerdestinations.store');
        Route::get('note-types/{type}/edit', fn () => 'edit')->name('notesmanagernotetypes.edit');
        Route::get('destinations/create', fn () => 'create')->name('clientsmanagerdestinations.create');
        Route::post('bananas', fn () => 'stored')->name('suckbanana.store');
        Route::patch('bananas/{banana}', fn () => 'updated')->name('suckbanana.update');
        Route::get('other-bananas/create', fn () => 'create')->name('suckbananas.create');
        Route::get('other-bananas/{banana}/edit', fn () => 'edit')->name('suckbananas.edit');
        Route::get('bananas/create/extra', fn () => 'extra')->name('suckbanana.create.extra');
        Route::get('bananas/{banana}/edit/extra', fn () => 'extra')->name('suckbanana.edit.extra');
        Route::get('destinations/store/preview', fn () => 'preview')->name('clientsmanagerdestinations.store.preview');

        $data = $this->getJson('/account-management/routes', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $html = collect($data->json('data'))->flatten()->implode('\n');
        foreach (['notesmanagernotetypes.update', 'clientsmanagerdestinations.store'] as $hidden) {
            $this->assertStringNotContainsString('>'.$hidden.'</span>', $html);
        }
        foreach (['notesmanagernotetypes.edit', 'clientsmanagerdestinations.create', 'suckbanana.store',
            'suckbanana.update', 'clientsmanagerdestinations.store.preview'] as $visible) {
            $this->assertStringContainsString('>'.$visible.'</span>', $html);
        }

        $rows = app(RouteTree::class)->rows(RouteRecord::where('is_registered', true)->get());
        $destinations = $rows->first(fn ($row) => $row->label === 'destinations' && $row->depth === 1);
        $this->assertEqualsCanonicalizing([
            $this->record('clientsmanagerdestinations.create')->id,
            $this->record('clientsmanagerdestinations.store.preview')->id,
        ], $destinations->route_ids);
    }

    public static function formRoutePairs(): array
    {
        return [['store', 'create'], ['update', 'edit']];
    }

    #[DataProvider('formRoutePairs')]
    public function test_hidden_write_routes_follow_form_roles_and_permissions_without_copying_associations(string $write, string $form): void
    {
        Route::middleware('web')->get('paired-form', fn () => 'form')->name('paired.'.$form);
        Route::middleware('web')->post('paired-write', fn () => 'saved')->name('paired.'.$write);
        $this->sync();
        $editor = $this->makeUserWithRole('editor');
        $this->actingAs($editor);
        $formRoute = $this->record('paired.'.$form);
        $role = Role::findByName('editor');

        $this->post('/paired-write')->assertForbidden();
        $formRoute->roles()->attach($role);
        $this->get('/paired-form')->assertOk();
        $this->post('/paired-write')->assertOk();
        $formRoute->roles()->detach($role);
        $this->post('/paired-write')->assertForbidden();

        $permission = Permission::findOrCreate('paired.write', 'web');
        $formRoute->permissions()->attach($permission);
        $editor->givePermissionTo($permission);
        $this->post('/paired-write')->assertOk();
        $formRoute->permissions()->detach($permission);
        $this->post('/paired-write')->assertForbidden();
        $formRoute->permissions()->attach($permission);
        $editor->revokePermissionTo($permission);
        $this->post('/paired-write')->assertForbidden();
        $role->givePermissionTo($permission);
        $editor->unsetRelation('roles');
        $this->post('/paired-write')->assertOk();

        $this->assertSame(0, $this->record('paired.'.$write)->roles()->count());
        $this->assertSame(0, $this->record('paired.'.$write)->permissions()->count());
    }

    #[DataProvider('formRoutePairs')]
    public function test_write_routes_use_their_own_rules_only_without_a_registered_form(string $write, string $form): void
    {
        Route::middleware('web')->post('paired-write', fn () => 'saved')->name('paired.'.$write);
        Route::middleware('web')->get('other-form', fn () => 'form')->name('other.'.$form);
        Route::middleware('web')->get('form-preview', fn () => 'preview')->name('paired.'.$form.'.preview');
        $this->sync();
        $this->actingAs($this->makeUserWithRole('editor'));
        $role = Role::findByName('editor');
        $this->record('paired.'.$write)->roles()->attach($role);
        $this->post('/paired-write')->assertOk();

        Route::middleware('web')->get('paired-form', fn () => 'form')->name('paired.'.$form);
        $this->sync();
        $this->post('/paired-write')->assertForbidden();
        $this->record('paired.'.$form)->roles()->attach(Role::findOrCreate('viewer', 'web'));
        $this->post('/paired-write')->assertForbidden();

        $routes = new \Illuminate\Routing\RouteCollection;
        foreach (app('router')->getRoutes() as $route) {
            if ($route->getName() !== 'paired.'.$form) $routes->add($route);
        }
        app('router')->setRoutes($routes);
        $this->sync();
        $this->post('/paired-write')->assertOk();
        $this->assertSame([$role->id], $this->record('paired.'.$write)->roles->modelKeys());
    }

    #[DataProvider('formRoutePairs')]
    public function test_unsynchronized_write_routes_cannot_inherit_access_from_registered_forms(string $write, string $form): void
    {
        Route::middleware('web')->get('paired-form', fn () => 'form')->name('paired.'.$form);
        $this->sync();
        $this->actingAs($this->makeUserWithRole('editor'));
        $this->record('paired.'.$form)->roles()->attach(Role::findByName('editor'));
        Route::middleware('web')->post('paired-write', fn () => 'saved')->name('paired.'.$write);
        $this->post('/paired-write')->assertForbidden();

        $this->sync();
        $this->post('/paired-write')->assertOk();
    }

    #[DataProvider('formRoutePairs')]
    public function test_write_routes_reappear_when_corresponding_form_is_no_longer_registered(string $write, string $form): void
    {
        $this->admin();
        Route::post('paired-write', fn () => 'saved')->name('paired.'.$write);
        Route::get('paired-form', fn () => 'form')->name('paired.'.$form);
        $data = $this->getJson('/account-management/routes', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $html = collect($data->json('data'))->flatten()->implode('\n');
        $this->assertStringNotContainsString('paired.'.$write, $html);
        $this->assertStringContainsString('paired.'.$form, $html);
        $this->assertStringNotContainsString('>paired-write</strong>', $html);

        $routes = new \Illuminate\Routing\RouteCollection;
        foreach (app('router')->getRoutes() as $route) {
            if ($route->getName() !== 'paired.'.$form) $routes->add($route);
        }
        app('router')->setRoutes($routes);
        $data = $this->getJson('/account-management/routes', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $this->assertStringContainsString('paired.'.$write, collect($data->json('data'))->flatten()->implode('\n'));
        $this->assertFalse($this->record('paired.'.$form)->is_registered);
    }

    public function test_opening_table_syncs_routes_on_every_visit_and_preserves_associations(): void
    {
        $this->admin();
        $this->assertDatabaseCount('accountmanager_routes', 0);
        $this->get('/account-management/routes')->assertOk()->assertDontSee('Sincronizza route');
        $record = $this->record();
        $role = Role::findOrCreate('editor', 'web');
        $permission = Permission::findOrCreate('clients.read', 'web');
        $record->roles()->attach($role);
        $record->permissions()->attach($permission);

        Route::middleware('web')->get('new-route', fn () => 'new')->name('new.route');
        $routes = new \Illuminate\Routing\RouteCollection;
        foreach (app('router')->getRoutes() as $route) {
            if ($route->getName() !== 'clients.edit') $routes->add($route);
        }
        app('router')->setRoutes($routes);
        $this->get('/account-management/routes')->assertOk();
        $this->assertTrue($this->record('new.route')->is_registered);
        $this->assertFalse($this->record('clients.edit')->is_registered);
        $this->assertSame($record->id, $this->record()->id);
        $this->assertSame($role->id, $this->record()->roles->sole()->id);
        $this->assertSame($permission->id, $this->record()->permissions->sole()->id);

        $count = RouteRecord::count();
        $this->get('/account-management/routes')->assertOk();
        $this->assertDatabaseCount('accountmanager_routes', $count);
        $this->assertDatabaseCount('accountmanager_route_role', 1);
        $this->assertDatabaseCount('accountmanager_route_permission', 1);
    }

    public function test_direct_ajax_table_loads_automatically_sync_new_routes_before_returning_rows(): void
    {
        $this->admin();
        $this->assertDatabaseCount('accountmanager_routes', 0);
        $first = $this->getJson('/account-management/routes', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $this->assertStringContainsString('clients.index', collect($first->json('data'))->flatten()->implode('\n'));

        Route::middleware('web')->get('ajax-new-route', fn () => 'new')->name('ajax.new.route');
        $next = $this->getJson('/account-management/routes', ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $this->assertStringContainsString('ajax.new.route', collect($next->json('data'))->flatten()->implode('\n'));
        $this->assertTrue($this->record('ajax.new.route')->is_registered);
    }

    public function test_automatic_sync_refuses_duplicate_route_names_without_changing_catalog(): void
    {
        $this->admin();
        $this->get('/account-management/routes')->assertOk();
        $count = RouteRecord::count();
        Route::middleware('web')->get('duplicate-one', fn () => 'one')->name('duplicate');
        Route::middleware('web')->get('duplicate-two', fn () => 'two')->name('duplicate');
        $this->getJson('/account-management/routes')->assertStatus(409);
        $this->assertSame($count, RouteRecord::where('is_registered', true)->count());
    }

    public function test_existing_published_model_configuration_uses_default_route_model_and_fields(): void
    {
        config()->offsetUnset('accountmanager.models.route');
        $this->sync();
        $this->admin();
        $this->get('/account-management/routes')->assertOk()->assertSee('accountmanager-route-associations', false);
        $this->getJson('/account-management/routes', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonStructure(['data']);
    }

    public function test_bulk_association_is_additive_idempotent_and_can_remove_selected_links_only(): void
    {
        $this->sync();
        $this->admin();
        $editor = Role::findOrCreate('editor', 'web');
        $viewer = Role::findOrCreate('viewer', 'web');
        $permission = Permission::findOrCreate('clients.read', 'web');
        $ids = RouteRecord::where('name', 'like', 'clients.%')->pluck('id')->all();
        $this->record()->roles()->attach($viewer);
        $payload = $this->association($ids, [$editor->id], [$permission->id]);
        $this->post('/account-management/routes/associate', $payload)->assertRedirect();
        $this->post('/account-management/routes/associate', $payload)->assertRedirect();
        foreach ($ids as $id) {
            $this->assertDatabaseHas('accountmanager_route_role', ['route_id' => $id, 'role_id' => $editor->id]);
            $this->assertDatabaseHas('accountmanager_route_permission', ['route_id' => $id, 'permission_id' => $permission->id]);
        }
        $this->assertSame(5, DB::table('accountmanager_route_role')->count());
        $this->post('/account-management/routes/associate', $this->association($ids, [$editor->id], [$permission->id], 'detach'))->assertRedirect();
        $this->assertSame(1, DB::table('accountmanager_route_role')->count());
        $this->assertSame(0, DB::table('accountmanager_route_permission')->count());
        $this->assertSame($viewer->id, $this->record()->roles->sole()->id);
    }

    public function test_management_denies_non_admin_even_with_an_explicit_route_permission(): void
    {
        $this->sync();
        $this->actingAs($this->makeUserWithRole('editor'));
        foreach (['routes.index', 'routes.associate'] as $name) {
            $this->record('accountmanager'.$name)->roles()->attach(Role::findByName('editor'));
        }
        $this->get('/account-management/routes')->assertForbidden();
        $this->post('/account-management/routes/associate')->assertForbidden();
        $this->assertSame(2, DB::table('accountmanager_route_role')->count());
    }

    public function test_bulk_validation_is_atomic_and_rejects_missing_unnamed_excluded_and_stale_records(): void
    {
        config(['accountmanager.routeAccess.exceptRoutes' => ['accountmanager.ldap.login']]);
        $this->sync();
        $this->admin();
        $editor = Role::findOrCreate('editor', 'web');
        $ids = [$this->record()->id];
        $badPayloads = [
            $this->association([], [$editor->id]), $this->association($ids),
            $this->association($ids, [999999]), $this->association([...$ids, 999999], [$editor->id]),
            $this->association([...$ids, RouteRecord::whereNull('name')->firstOrFail()->id], [$editor->id]),
            $this->association([...$ids, $this->record('accountmanager.ldap.login')->id], [$editor->id]),
            $this->association($ids, [$editor->id], [], 'replace'),
            ['route_ids' => 'invalid', 'roles' => [$editor->id], 'operation' => 'attach'],
            ['route_ids' => '42', 'roles' => [$editor->id], 'operation' => 'attach'],
        ];
        foreach ($badPayloads as $payload) $this->postJson('/account-management/routes/associate', $payload)->assertUnprocessable();
        $this->record()->update(['is_registered' => false]);
        $this->postJson('/account-management/routes/associate', $this->association($ids, [$editor->id]))->assertUnprocessable();
        $this->assertSame(0, DB::table('accountmanager_route_role')->count());
    }

    public function test_management_mutations_require_csrf_tokens(): void
    {
        $this->sync();
        $this->admin();
        $this->app->instance('env', 'production');
        try {
            $this->postJson('/account-management/routes/associate')->assertStatus(419);
        } finally {
            $this->app->instance('env', 'testing');
        }
        $this->assertDatabaseCount('accountmanager_route_role', 0);
    }

    public function test_management_menu_link_is_available_only_when_enabled_for_bypass_roles(): void
    {
        $this->admin('superadministrator');
        config(['accountmanager.enabled' => true, 'accountmanager.logUserAccess.enabled' => false]);
        app('accountmanager')->manageMenuButtons();
        app('menu')->setUsesCache(false);
        $this->assertStringContainsString(route('accountmanagerroutes.index'), app('menu')->render());
        $this->app->forgetInstance('menu');
        config(['accountmanager.routeAccess.enabled' => false]);
        app('accountmanager')->manageMenuButtons();
        app('menu')->setUsesCache(false);
        $this->assertStringNotContainsString(route('accountmanagerroutes.index'), app('menu')->render());
    }

    public function test_existing_package_role_middleware_uses_new_rules_only_when_enabled(): void
    {
        $this->sync();
        $this->actingAs($this->makeUserWithRole('editor'));
        $this->record('accountmanagerusers.index')->roles()->attach(Role::findByName('editor'));
        $this->get('/account-management/users')->assertOk();
        config(['accountmanager.routeAccess.enabled' => false]);
        $this->get('/account-management/users')->assertForbidden();
    }

    public function test_helper_sync_and_migration_rollback_preserve_roles_and_permissions(): void
    {
        $role = Role::findOrCreate('editor', 'web');
        $permission = Permission::findOrCreate('clients.read', 'web');
        RouteSyncHelper::sync();
        $this->record()->roles()->attach($role);
        $this->record()->permissions()->attach($permission);
        $migration = require __DIR__.'/../../database/migrations/2026_11_13_000000_create_accountmanager_routes_tables.php';
        $migration->down();
        $this->assertFalse(Schema::hasTable('accountmanager_routes'));
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
        $this->assertDatabaseHas('permissions', ['id' => $permission->id]);
        $migration->up();
        $this->assertDatabaseCount('accountmanager_routes', 0);
    }
}

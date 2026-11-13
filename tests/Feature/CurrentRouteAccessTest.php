<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Helpers\RouteSyncHelper;
use IlBronza\AccountManager\Models\Permission;
use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Models\Route as CatalogRoute;
use IlBronza\AccountManager\Tests\TestCase;
use IlBronza\AccountManager\View\CurrentRouteAccessComposer;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;

class CurrentRouteAccessTest extends TestCase
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
            Route::get('route-page/{record}', fn () => view('uikittemplate::app'))->name('fixture.page');
            Route::get('route-other', fn () => 'other')->name('fixture.other');
            Route::get('route-edit', fn () => 'edit')->name('fixture.edit');
            Route::put('route-edit', fn () => 'saved')->name('fixture.update');
            Route::get('route-unnamed', fn () => view('uikittemplate::app'));
        });
    }

    private function record(string $name = 'fixture.page'): CatalogRoute
    {
        return CatalogRoute::whereName($name)->sole();
    }

    private function payload(array $roles = [], array $permissions = [], string $name = 'fixture.page'): array
    {
        return ['route_name' => $name, 'roles' => $roles, 'permissions' => $permissions];
    }

    public static function managementRoles(): array
    {
        return [['superadmin'], ['administrator'], ['routesManager'], ['superadministrator']];
    }

    #[DataProvider('managementRoles')]
    public function test_managers_can_load_and_replace_current_page_associations(string $role): void
    {
        $this->actingAs($this->makeUserWithRole($role));
        $editor = Role::findOrCreate('editor', 'web');
        $permission = Permission::findOrCreate('page.read', 'web');
        $this->getJson('/account-management/routes/access?route_name=fixture.page')
            ->assertOk()->assertJsonPath('route_name', 'fixture.page')
            ->assertJsonPath('selected_roles', [])->assertJsonFragment(['name' => 'editor', 'guard' => 'web']);
        $this->putJson('/account-management/routes/access', $this->payload([$editor->id], [$permission->id]))->assertOk();
        $this->getJson('/account-management/routes/access?route_name=fixture.page')
            ->assertOk()->assertJsonPath('selected_roles', [$editor->id])->assertJsonPath('selected_permissions', [$permission->id]);
        $this->assertDatabaseCount('accountmanager_route_role', 1);
        $this->assertDatabaseCount('accountmanager_route_permission', 1);
    }

    public function test_routes_manager_can_manage_the_catalog_without_bypassing_unrelated_pages(): void
    {
        // The management routes must work even when the host removes the default exclusions.
        config(['accountmanager.routeAccess.exceptUris' => []]);
        $user = $this->makeUserWithRole('routesManager');
        $this->actingAs($user);
        $this->get('/route-other')->assertForbidden();
        $this->get('/account-management/routes')->assertOk();
        $editor = Role::findOrCreate('editor', 'web');
        $this->post('/account-management/routes/associate', [
            'route_ids' => json_encode([$this->record()->id]), 'operation' => 'attach', 'roles' => [$editor->id],
        ])->assertRedirect();
        $this->getJson('/account-management/routes/access?route_name=fixture.other')->assertOk();
        $this->putJson('/account-management/routes/access', $this->payload([$user->roles->sole()->id], [], 'fixture.other'))->assertOk();
        $this->get('/route-other')->assertOk();
    }

    public function test_empty_selection_revokes_access_and_only_updates_the_target_route(): void
    {
        RouteSyncHelper::sync();
        $editor = $this->makeUserWithRole('editor');
        $role = $editor->roles->sole();
        $this->record()->roles()->attach($role);
        $this->record('fixture.other')->roles()->attach($role);
        $this->actingAs($editor)->get('/route-page/123')->assertOk();
        $this->actingAs($this->makeUserWithRole('administrator', 'admin@example.test'));
        $this->putJson('/account-management/routes/access', $this->payload())->assertOk();
        $this->actingAs($editor)->get('/route-page/123')->assertForbidden();
        $this->get('/route-other')->assertOk();
        $this->assertDatabaseCount('accountmanager_route_role', 1);
    }

    public function test_edit_page_updates_also_control_the_corresponding_write_route(): void
    {
        $editor = $this->makeUserWithRole('editor');
        $this->actingAs($this->makeUserWithRole('superadmin', 'admin@example.test'));
        $this->getJson('/account-management/routes/access?route_name=fixture.edit')->assertOk();
        $this->putJson('/account-management/routes/access', $this->payload([$editor->roles->sole()->id], [], 'fixture.edit'))->assertOk();
        $this->actingAs($editor)->put('/route-edit')->assertOk();
        $this->assertCount(0, $this->record('fixture.update')->roles);
    }

    public function test_validation_is_atomic_and_does_not_silently_clear_missing_selections(): void
    {
        RouteSyncHelper::sync();
        $this->actingAs($this->makeUserWithRole('superadmin'));
        $role = Role::findOrCreate('editor', 'web');
        $this->record()->roles()->attach($role);
        foreach ([
            $this->payload([$role->id], [999999]), $this->payload([999999]),
            $this->payload([$role->id, $role->id]),
            ['route_name' => 'fixture.page', 'roles' => []],
            ['route_name' => 'fixture.page', 'permissions' => []],
        ] as $input) {
            $this->putJson('/account-management/routes/access', $input)->assertUnprocessable();
        }
        $this->assertSame([$role->id], $this->record()->roles->modelKeys());
    }

    public function test_management_endpoints_reject_ordinary_guests_suspended_users_and_disabled_feature(): void
    {
        $this->getJson('/account-management/routes/access?route_name=fixture.page')->assertUnauthorized();
        $this->actingAs($this->makeUserWithRole('editor'));
        $this->getJson('/account-management/routes/access?route_name=fixture.page')->assertForbidden();
        $this->putJson('/account-management/routes/access', $this->payload())->assertForbidden();
        $admin = $this->makeUserWithRole('administrator', 'admin@example.test');
        $this->actingAs($admin);
        config(['accountmanager.routeAccess.enabled' => false]);
        $this->getJson('/account-management/routes/access?route_name=fixture.page')->assertNotFound();
        $this->putJson('/account-management/routes/access', $this->payload())->assertNotFound();
        config(['accountmanager.routeAccess.enabled' => true]);
        $admin->updateQuietly(['active' => false]);
        $this->getJson('/account-management/routes/access?route_name=fixture.page')->assertForbidden();
        $this->assertDatabaseCount('accountmanager_route_role', 0);
    }

    public function test_excluded_missing_write_only_and_stale_routes_cannot_be_edited(): void
    {
        RouteSyncHelper::sync();
        $this->actingAs($this->makeUserWithRole('superadmin'));
        config(['accountmanager.routeAccess.exceptRoutes' => ['fixture.other']]);
        foreach (['missing', 'fixture.other', 'fixture.update'] as $name) {
            $this->getJson('/account-management/routes/access?route_name='.$name)->assertNotFound();
            $this->putJson('/account-management/routes/access', $this->payload([], [], $name))->assertNotFound();
        }
        $this->record()->update(['is_registered' => false]);
        $this->putJson('/account-management/routes/access', $this->payload())->assertNotFound();
        $this->assertDatabaseCount('accountmanager_route_role', 0);
    }

    public function test_updates_require_a_valid_csrf_token(): void
    {
        RouteSyncHelper::sync();
        $this->actingAs($this->makeUserWithRole('routesManager'));
        $this->app->instance('env', 'production');
        try {
            $this->putJson('/account-management/routes/access', $this->payload())->assertStatus(419);
        } finally {
            $this->app->instance('env', 'testing');
        }
    }

    public function test_page_modal_and_button_use_the_current_route_and_keep_existing_extra_views(): void
    {
        $this->actingAs($this->makeUserWithRole('superadmin'));
        app('accountmanager')->manageMenuButtons();
        $button = app('menu')->getButtonByName('accountmanager.currentRouteAccess');
        $this->assertSame('mainRightNavbar', $button->getNavbar()->getName());
        $this->assertSame('#accountmanager-current-route-access', $button->getToggle());
        $this->get('/route-page/321')->assertOk()->assertSee('id="accountmanager-current-route-access"', false)
            ->assertSee('uk-toggle="target: #accountmanager-current-route-access"', false)
            ->assertSee('data-route-name="fixture.page"', false)->assertSee('GET|HEAD /route-page/{record}');

        $view = view('uikittemplate::app', ['extraViews' => ['bottom' => ['existing.view' => ['example' => true]]]]);
        app(CurrentRouteAccessComposer::class)->compose($view);
        $this->assertSame(['example' => true], $view->getData()['extraViews']['bottom']['existing.view']);
        $this->get('/route-unnamed')->assertOk()->assertSee(__('accountmanager::route-access.unnamed'))
            ->assertDontSee('data-current-route-form', false);
    }

    public function test_excluded_page_explains_why_associations_cannot_be_edited(): void
    {
        $this->actingAs($this->makeUserWithRole('administrator'));
        config(['accountmanager.routeAccess.exceptRoutes' => ['fixture.page']]);
        $this->get('/route-page/123')->assertOk()->assertSee(__('accountmanager::route-access.excluded_help'))
            ->assertDontSee('data-current-route-form', false);
    }

    public function test_ordinary_user_and_disabled_feature_do_not_receive_the_modal_or_button(): void
    {
        $user = $this->makeUserWithRole('editor');
        RouteSyncHelper::sync();
        $this->record()->roles()->attach($user->roles->sole());
        $this->actingAs($user);
        app('accountmanager')->manageMenuButtons();
        $this->assertNull(app('menu')->getButtonByName('accountmanager.currentRouteAccess'));
        $this->get('/route-page/123')->assertOk()->assertDontSee('id="accountmanager-current-route-access"', false);
        config(['accountmanager.routeAccess.enabled' => false]);
        Schema::drop('accountmanager_route_role');
        Schema::drop('accountmanager_route_permission');
        Schema::drop('accountmanager_routes');
        $this->actingAs($this->makeUserWithRole('superadmin', 'admin@example.test'));
        $this->get('/route-page/123')->assertOk()->assertDontSee('id="accountmanager-current-route-access"', false);
    }

    public function test_role_migration_is_idempotent_preserves_assignments_and_grants_no_user_roles(): void
    {
        $migration = require __DIR__.'/../../database/migrations/2026_11_13_000003_create_routes_manager_role.php';
        $migration->up();
        $migration->up();
        $this->assertDatabaseCount('roles', 1);
        $this->assertDatabaseCount('model_has_roles', 0);
        $user = $this->makeUserWithRole('routesManager');
        $migration->down();
        $this->assertTrue($user->fresh()->hasRole('routesManager'));
    }

    public function test_bypass_roles_are_implicit_and_hidden_from_the_modal_scope(): void
    {
        $this->actingAs($this->makeUserWithRole('routesManager'));
        $bypassIds = [];
        foreach (['superadmin', 'administrator', 'superadministrator'] as $name) {
            $bypassIds[] = Role::findOrCreate($name, 'web')->id;
        }
        $editor = Role::findOrCreate('editor', 'web');
        // Permission names are independent of role names and remain selectable.
        Permission::findOrCreate('administrator', 'web');
        foreach (Route::getRoutes() as $route) {
            if ($route->getName() === 'fixture.page') $route->middleware('role:superadmin|administrator|editor');
        }
        RouteSyncHelper::sync();
        $this->record()->roles()->attach([...$bypassIds, $editor->id]);

        $response = $this->getJson('/account-management/routes/access?route_name=fixture.page')->assertOk()
            ->assertJsonPath('selected_roles', [$editor->id])->assertJsonPath('system_roles', ['editor']);
        $this->assertSame(['editor', 'routesManager'], array_column($response->json('roles'), 'name'));
        $this->assertSame(['administrator'], array_column($response->json('permissions'), 'name'));
    }

    public function test_manual_requests_cannot_associate_or_detach_bypass_roles(): void
    {
        $this->actingAs($this->makeUserWithRole('routesManager'));
        RouteSyncHelper::sync();
        $editor = Role::findOrCreate('editor', 'web');
        $permission = Permission::findOrCreate('page.read', 'web');
        $this->record()->roles()->attach($editor);
        foreach (['superadmin', 'administrator', 'superadministrator'] as $name) {
            $role = Role::findOrCreate($name, 'web');
            $this->putJson('/account-management/routes/access', $this->payload([$role->id], [$permission->id]))
                ->assertUnprocessable()->assertJsonValidationErrors('roles');
            foreach (['attach', 'detach'] as $operation) {
                $this->postJson('/account-management/routes/associate', [
                    'route_ids' => json_encode([$this->record()->id]), 'operation' => $operation,
                    'roles' => [$role->id], 'permissions' => [$permission->id],
                ])->assertUnprocessable()->assertJsonValidationErrors('roles');
            }
        }
        $this->assertSame([$editor->id], $this->record()->roles->modelKeys());
        $this->assertDatabaseCount('accountmanager_route_permission', 0);
    }

    public function test_modal_saves_preserve_historical_links_outside_the_assignable_scope(): void
    {
        $this->actingAs($this->makeUserWithRole('routesManager'));
        // The same rule applies to host-defined bypass roles.
        config(['accountmanager.routeAccess.bypassRoles' => ['superadmin', 'administrator', 'globalAccess']]);
        RouteSyncHelper::sync();
        $implicitIds = [];
        foreach (['superadmin', 'administrator', 'globalAccess'] as $name) {
            $implicitIds[] = Role::findOrCreate($name, 'web')->id;
        }
        $editor = Role::findOrCreate('editor', 'web');
        $viewer = Role::findOrCreate('viewer', 'web');
        $this->record()->roles()->attach([...$implicitIds, $editor->id]);
        $response = $this->getJson('/account-management/routes/access?route_name=fixture.page')->assertOk();
        $this->assertNotContains('globalAccess', array_column($response->json('roles'), 'name'));
        $this->putJson('/account-management/routes/access', $this->payload([$viewer->id]))->assertOk();
        $this->assertEqualsCanonicalizing([...$implicitIds, $viewer->id], $this->record()->roles->modelKeys());
        $this->putJson('/account-management/routes/access', $this->payload())->assertOk();
        $this->assertEqualsCanonicalizing($implicitIds, $this->record()->roles->modelKeys());
    }

    public function test_catalog_choices_and_cells_exclude_implicit_bypass_roles(): void
    {
        $this->actingAs($this->makeUserWithRole('routesManager'));
        $bypassIds = [];
        foreach (['superadmin', 'administrator', 'superadministrator'] as $name) {
            $bypassIds[] = Role::findOrCreate($name, 'web')->id;
        }
        $editor = Role::findOrCreate('editor', 'web');
        RouteSyncHelper::sync();
        $this->record()->roles()->attach([...$bypassIds, $editor->id]);
        $response = $this->get('/account-management/routes')->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $options = (new \DOMXPath($dom))->query('//select[@id="route-access-roles"]/option');
        $this->assertSame(['editor (web)', 'routesManager (web)'],
            array_map(fn ($option) => $option->textContent, iterator_to_array($options)));

        $record = $this->record();
        $record->system_roles = ['superadmin', 'administrator', 'superadministrator', 'editor'];
        $entry = (object) ['route' => $record, 'excluded' => false];
        foreach (['assignments', 'system_roles'] as $cell) {
            $html = view('accountmanager::routes.cell', compact('entry', 'cell'))->render();
            foreach (['superadmin', 'administrator', 'superadministrator'] as $name) {
                $this->assertStringNotContainsString('>'.$name.'</span>', $html);
            }
            $this->assertStringContainsString('>editor</span>', $html);
        }
        $record->roles()->detach($editor);
        $record->unsetRelation('roles');
        $this->assertStringContainsString(__('accountmanager::route-access.denied'),
            view('accountmanager::routes.cell', ['entry' => $entry, 'cell' => 'assignments'])->render());
    }
}

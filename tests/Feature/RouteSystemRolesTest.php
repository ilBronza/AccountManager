<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Helpers\RouteSyncHelper;
use IlBronza\AccountManager\Models\Permission;
use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Models\Route as CatalogRoute;
use IlBronza\AccountManager\Tests\TestCase;
use IlBronza\CRUD\Middleware\CRUDBasePackageMiddlewareRolesPermissions;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\RedirectController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Middleware\RoleMiddleware;

class RouteSystemRolesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['000000_create_accountmanager_routes_tables', '000001_add_description_to_accountmanager_routes_table',
            '000002_add_system_roles_to_accountmanager_routes_table'] as $migration) {
            (require __DIR__.'/../../database/migrations/2026_11_13_'.$migration.'.php')->up();
        }
        config(['accountmanager.routeAccess.enabled' => true]);
        app('router')->aliasMiddleware('fixture.roles', SystemRolesPackageMiddleware::class);
    }

    private function record(string $name): CatalogRoute
    {
        return CatalogRoute::whereName($name)->sole();
    }

    public function test_sync_reads_roles_from_resolved_groups_aliases_guards_and_controller_middleware(): void
    {
        app('router')->aliasMiddleware('team', RoleMiddleware::class);
        app('router')->middlewareGroup('staff', ['web', 'team:editor|viewer,api']);
        Route::middleware(['staff', RoleMiddleware::class.':viewer|administrator'])->get('staff', fn () => 'ok')->name('staff');
        Route::get('controller-roles', [SystemRolesController::class, 'index'])->name('controller.roles');
        Route::middleware('role:unnamed-role')->get('unnamed-roles', fn () => 'ok');

        RouteSyncHelper::sync();

        $this->assertSame(['editor', 'viewer', 'administrator'], $this->record('staff')->system_roles);
        $this->assertSame(['controller-role'], $this->record('controller.roles')->system_roles);
        $this->assertSame(['unnamed-role'], CatalogRoute::whereUri('unnamed-roles')->sole()->system_roles);
        $this->assertDatabaseCount('roles', 0);
        $this->assertDatabaseCount('accountmanager_route_role', 0);
    }

    public function test_excluded_middleware_and_permission_only_routes_do_not_supply_roles(): void
    {
        app('router')->middlewareGroup('staff', ['web', 'role:editor']);
        Route::middleware('staff')->withoutMiddleware('role:editor')->get('excluded', fn () => 'ok')->name('excluded');
        Route::middleware(['auth', 'permission:clients.read'])->get('permission-only', fn () => 'ok')->name('permission-only');
        Route::get('plain', fn () => 'ok')->name('plain');
        config(['fixture.defaultRoles' => ['must-not-be-inferred']]);

        RouteSyncHelper::sync();

        foreach (['excluded', 'permission-only', 'plain'] as $name) {
            $this->assertSame([], $this->record($name)->system_roles);
        }
    }

    public function test_package_middleware_uses_its_route_rules_defaults_and_custom_resolver(): void
    {
        config(['fixture.defaultRoles' => ' editor | viewer | editor ',
            'fixture.routeRoles' => ['fixture' => ['special' => ['manager']]]]);
        Route::middleware('fixture.roles')->group(function () {
            Route::get('default-roles', fn () => 'ok')->name('fixture.default');
            Route::get('special-roles', fn () => 'ok')->name('fixture.special');
        });
        Route::middleware(SystemRolesOverrideMiddleware::class)->get('custom-roles', fn () => 'ok')->name('fixture.custom');

        RouteSyncHelper::sync();

        $this->assertSame(['editor', 'viewer'], $this->record('fixture.default')->system_roles);
        $this->assertSame(['manager'], $this->record('fixture.special')->system_roles);
        $this->assertSame(['resolved-by-fixture.custom'], $this->record('fixture.custom')->system_roles);
    }

    public function test_accountmanager_preserves_empty_rules_boolean_maps_and_custom_route_prefixes(): void
    {
        config(['accountmanager.routePrefix' => 'custom.',
            'accountmanager.defaultRoles' => ['enabled-role' => true, 'disabled-role' => false],
            'accountmanager.routeRoles' => [
                'accountmanagerusers' => ['index' => ['users-manager']],
                'custom' => ['denied' => []],
            ]]);
        foreach (['users.index', 'denied', 'fallback'] as $suffix) {
            Route::middleware('accountmanager.roles')->get('custom/'.$suffix, fn () => 'ok')->name('custom.'.$suffix);
        }

        RouteSyncHelper::sync();

        $this->assertSame(['users-manager'], $this->record('custom.users.index')->system_roles);
        $this->assertSame([], $this->record('custom.denied')->system_roles);
        $this->assertSame(['enabled-role'], $this->record('custom.fallback')->system_roles);
        $this->assertSame(['superadmin', 'superadministrator', 'administrator', 'routesManager'],
            $this->record('accountmanagerroutes.index')->system_roles);
    }

    public function test_repeated_sync_refreshes_system_roles_without_creating_or_changing_assignments(): void
    {
        config(['fixture.defaultRoles' => ['old-role']]);
        $route = Route::middleware('fixture.roles')->get('refresh', fn () => 'ok')->name('refresh');
        RouteSyncHelper::sync();
        $record = $this->record('refresh');
        $record->roles()->attach(Role::findOrCreate('manually-assigned', 'web'));
        $record->permissions()->attach(Permission::findOrCreate('manual.permission', 'web'));

        config(['fixture.defaultRoles' => ['new-role']]);
        RouteSyncHelper::sync();
        $this->assertSame(['new-role'], $record->fresh()->system_roles);

        $route->withoutMiddleware('fixture.roles');
        RouteSyncHelper::sync();
        $this->assertSame([], $record->fresh()->system_roles);
        $this->assertSame($record->id, $this->record('refresh')->id);
        $this->assertSame('manually-assigned', $record->roles()->sole()->name);
        $this->assertSame('manual.permission', $record->permissions()->sole()->name);
        $this->assertDatabaseCount('roles', 1);
        $this->assertDatabaseCount('permissions', 1);
    }

    public function test_table_refreshes_system_roles_and_escapes_names_in_a_separate_column(): void
    {
        $role = 'editor<script>alert("test")</script>';
        config(['fixture.defaultRoles' => [$role]]);
        Route::middleware('fixture.roles')->get('table-roles', fn () => 'ok')->name('table.roles');
        Route::get('plain', fn () => 'ok')->name('plain');
        $this->actingAs($this->makeUserWithRole('administrator'));

        $this->get('/account-management/routes')->assertOk()->assertSee('Ruoli previsti dal sistema');
        $rows = $this->getJson('/account-management/routes', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data');
        $record = $this->record('table.roles');
        $row = collect($rows)->first(fn ($row) => $row[0] === 'route-'.$record->id);
        $this->assertStringContainsString(e($role), $row[7]);
        $this->assertStringNotContainsString('<script>', $row[7]);
        $this->assertStringContainsString('Accesso negato salvo amministratori', $row[8]);
        $this->assertSame([$role], $record->system_roles);
        foreach ($rows as $row) {
            if (str_starts_with($row[0], 'branch-') || $row[0] === 'route-'.$this->record('plain')->id) {
                $this->assertSame('', trim($row[7]));
            }
        }
        $this->assertDatabaseCount('accountmanager_route_role', 0);
    }

    public function test_cached_routes_keep_middleware_roles_and_package_configuration(): void
    {
        $router = new Router(app('events'), app());
        $router->aliasMiddleware('role', RoleMiddleware::class);
        $router->aliasMiddleware('fixture.roles', SystemRolesPackageMiddleware::class);
        $router->get('cached-roles', RedirectController::class)->middleware(['role:reader|editor,api', 'fixture.roles'])->name('cached.roles');
        foreach ($router->getRoutes() as $route) $route->prepareForSerialization();
        $cached = new Router(app('events'), app());
        $cached->aliasMiddleware('role', RoleMiddleware::class);
        $cached->aliasMiddleware('fixture.roles', SystemRolesPackageMiddleware::class);
        $cached->setCompiledRoutes($router->getRoutes()->compile());
        config(['fixture.defaultRoles' => ['package-role']]);

        RouteSyncHelper::sync($cached);

        $this->assertSame(['reader', 'editor', 'package-role'], $this->record('cached.roles')->system_roles);
    }

    public function test_migration_upgrades_and_rolls_back_without_losing_associations(): void
    {
        RouteSyncHelper::sync();
        $record = $this->record('accountmanagerusers.index');
        $record->roles()->attach(Role::findOrCreate('reader', 'web'));
        $record->permissions()->attach(Permission::findOrCreate('users.read', 'web'));
        $migration = require __DIR__.'/../../database/migrations/2026_11_13_000002_add_system_roles_to_accountmanager_routes_table.php';

        $migration->down();
        $this->assertFalse(Schema::hasColumn('accountmanager_routes', 'system_roles'));
        $this->assertSame($record->id, $record->fresh()->id);
        $migration->up();
        RouteSyncHelper::sync();

        $this->assertSame(['superadmin', 'accountManagerUsers'], $record->fresh()->system_roles);
        $this->assertSame('reader', $record->roles()->sole()->name);
        $this->assertSame('users.read', $record->permissions()->sole()->name);
    }
}

class SystemRolesPackageMiddleware extends CRUDBasePackageMiddlewareRolesPermissions
{
    protected string $configPackageName = 'fixture';
}

class SystemRolesOverrideMiddleware extends SystemRolesPackageMiddleware
{
    protected function getRolesForCurrentRoute(Request $request): array
    {
        return ['resolved-by-'.$request->route()->getName()];
    }
}

class SystemRolesController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:controller-role');
    }

    public function index(): string
    {
        return 'ok';
    }
}

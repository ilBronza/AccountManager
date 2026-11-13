<?php

namespace IlBronza\AccountManager\Tests\Feature;

use Composer\InstalledVersions;
use IlBronza\AccountManager\Helpers\RouteSyncHelper;
use IlBronza\AccountManager\Models\Permission;
use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Models\Route as CatalogRoute;
use IlBronza\AccountManager\Routing\RouteDescriptions;
use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\RedirectController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use UnexpectedValueException;

class RouteDescriptionsTest extends TestCase
{
    private array $installed;
    private array $fixturePackages = [];
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->installed = InstalledVersions::getAllRawData()[0];
        $this->directory = sys_get_temp_dir().'/route-descriptions-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700, true);
        (require __DIR__.'/../../database/migrations/2026_11_13_000000_create_accountmanager_routes_tables.php')->up();
        (require __DIR__.'/../../database/migrations/2026_11_13_000001_add_description_to_accountmanager_routes_table.php')->up();
        (require __DIR__.'/../../database/migrations/2026_11_13_000002_add_system_roles_to_accountmanager_routes_table.php')->up();
        config(['accountmanager.routeAccess.enabled' => true, 'accountmanager.routeAccess.exceptUris' => []]);
    }

    protected function tearDown(): void
    {
        InstalledVersions::reload($this->installed);
        (new Filesystem)->deleteDirectory($this->directory);
        parent::tearDown();
    }

    private function package(string $name, mixed $descriptions): string
    {
        $path = $this->directory.'/'.$name;
        mkdir($path.'/config', 0700, true);
        $file = $path.'/config/route-descriptions.php';
        $this->write($file, $descriptions);
        $this->installPath($name, $path);

        return $file;
    }

    private function installPath(string $name, ?string $path): void
    {
        $this->fixturePackages['description-tests/'.$name] = ['install_path' => $path];
        $data = $this->installed;
        $data['versions'] = array_replace($data['versions'], $this->fixturePackages);
        InstalledVersions::reload($data);
    }

    private function write(string $file, mixed $descriptions): void
    {
        file_put_contents($file, "<?php\nreturn ".var_export($descriptions, true).";\n");
    }

    private function record(string $name = 'clients.index'): CatalogRoute
    {
        return CatalogRoute::whereName($name)->sole();
    }

    public function test_sync_discovers_installed_package_files_and_matches_only_exact_registered_names(): void
    {
        $this->package('clients', ['clients.index' => 'Elenco dei clienti', 'clients.*' => 'Non è un pattern', 'absent.index' => 'Route non registrata']);
        $this->package('reports', ['reports.index' => 'Elenco dei rapporti']);
        // Metapackages and packages without a description file are optional.
        $this->installPath('meta', null);
        $this->installPath('no-file', $this->directory);
        Route::get('clients', fn () => 'ok')->name('clients.index');
        Route::get('clients/create', fn () => 'ok')->name('clients.create');
        Route::get('reports', fn () => 'ok')->name('reports.index');
        Route::get('unnamed-description-test', fn () => 'ok');

        RouteSyncHelper::sync();

        $this->assertSame('Elenco dei clienti', $this->record()->description);
        $this->assertSame('Elenco dei rapporti', $this->record('reports.index')->description);
        $this->assertNull($this->record('clients.create')->description);
        $this->assertNull(CatalogRoute::whereUri('unnamed-description-test')->sole()->description);
        $this->assertDatabaseMissing('accountmanager_routes', ['name' => 'absent.index']);
    }

    public function test_application_overrides_route_actions_which_override_packages_only_for_empty_descriptions(): void
    {
        $this->package('clients', ['clients.index' => 'Dal package']);
        $route = Route::get('clients', ['uses' => fn () => 'ok', 'description' => 'Dalla route'])->name('clients.index');
        config(['route-descriptions' => ['clients.index' => 'Dall’applicazione']]);
        RouteSyncHelper::sync();
        $this->assertSame('Dall’applicazione', $this->record()->description);

        config(['route-descriptions' => []]);
        RouteSyncHelper::sync();
        $this->assertSame('Dall’applicazione', $this->record()->description);
        $this->record()->update(['description' => null]);
        RouteSyncHelper::sync();
        $this->assertSame('Dalla route', $this->record()->description);

        $action = $route->getAction();
        unset($action['description']);
        $route->setAction($action);
        RouteSyncHelper::sync();
        $this->assertSame('Dalla route', $this->record()->description);
        $this->record()->update(['description' => '']);
        RouteSyncHelper::sync();
        $this->assertSame('Dal package', $this->record()->description);
    }

    public function test_repeated_sync_preserves_populated_descriptions_and_associations_when_sources_change_or_disappear(): void
    {
        $file = $this->package('clients', ['clients.index' => 'Descrizione iniziale']);
        Route::get('clients', fn () => 'ok')->name('clients.index');
        $this->app->singleton(RouteDescriptions::class);
        RouteSyncHelper::sync();
        $record = $this->record();
        $record->roles()->attach(Role::findOrCreate('reader', 'web'));
        $record->permissions()->attach(Permission::findOrCreate('clients.read', 'web'));

        $this->write($file, ['clients.index' => 'Descrizione aggiornata']);
        RouteSyncHelper::sync();
        $this->assertSame('Descrizione iniziale', $this->record()->description);
        $record->update(['description' => 'Descrizione personalizzata']);
        RouteSyncHelper::sync();
        $this->assertSame('Descrizione personalizzata', $this->record()->description);
        $record->update(['description' => '']);
        RouteSyncHelper::sync();
        $this->assertSame('Descrizione aggiornata', $this->record()->description);
        unlink($file);
        RouteSyncHelper::sync();
        $this->assertSame('Descrizione aggiornata', $this->record()->description);
        $record->update(['description' => null]);
        RouteSyncHelper::sync();
        $this->assertNull($this->record()->description);
        $this->assertSame($record->id, $this->record()->id);
        $this->assertSame(1, $this->record()->roles()->count());
        $this->assertSame(1, $this->record()->permissions()->count());
    }

    public function test_sync_fills_null_empty_and_whitespace_descriptions_but_preserves_zero(): void
    {
        Route::get('clients', fn () => 'ok')->name('clients.index');
        config(['route-descriptions' => ['clients.index' => 'Descrizione importata']]);
        RouteSyncHelper::sync();
        $record = $this->record();

        foreach ([null, '', '   ', '0'] as $description) {
            $record->update(['description' => $description]);
            RouteSyncHelper::sync();
            $this->assertSame($description === '0' ? '0' : 'Descrizione importata', $record->fresh()->description);
        }
    }

    public function test_description_can_be_edited_from_the_table_and_survives_page_and_ajax_syncs(): void
    {
        Route::get('clients', fn () => 'ok')->name('clients.index');
        config(['route-descriptions' => ['clients.index' => 'Descrizione importata']]);
        $this->actingAs($this->makeUserWithRole('administrator'));
        $this->get('/account-management/routes')->assertOk();
        $record = $this->record();
        $description = 'Descrizione utente "cliente" <script>alert("test")</script>';
        $url = route('accountmanagerroutes.update', ['route' => $record->id]);

        $this->postJson($url, ['_method' => 'PUT', 'ib-editor' => true, 'field' => 'description', 'value' => $description, 'name' => 'forged'])
            ->assertOk()->assertJson(['success' => true, 'update-editor' => true, 'field' => 'description',
                'value' => $description, 'action' => 'reloadTable']);
        $this->get('/account-management/routes')->assertOk();
        $rows = $this->getJson('/account-management/routes', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data');
        $row = collect($rows)->first(fn ($row) => $row[0] === 'route-'.$record->id);
        $this->assertStringContainsString('class="uk-input ib-editor-text"', $row[6]);
        $this->assertStringContainsString('data-field="description"', $row[6]);
        $this->assertStringContainsString('data-url="'.e($url).'"', $row[6]);
        $this->assertStringContainsString('value="'.e($description).'"', $row[6]);
        $this->assertStringNotContainsString('<script>', $row[6]);
        $this->assertSame($description, $record->fresh()->description);
        $this->assertSame('clients.index', $record->fresh()->name);

        $this->putJson($url, ['ib-editor' => true, 'field' => 'description', 'value' => ''])
            ->assertOk()->assertJson(['value' => null]);
        $this->assertNull($record->fresh()->description);
        RouteSyncHelper::sync();
        $this->assertSame('Descrizione importata', $record->fresh()->description);
    }

    public function test_description_editor_rejects_other_fields_invalid_values_and_generic_editor_actions(): void
    {
        $this->actingAs($this->makeUserWithRole('administrator'));
        RouteSyncHelper::sync();
        $record = $this->record('accountmanagerusers.index');
        $description = $record->description;
        $url = route('accountmanagerroutes.update', ['route' => $record->id]);
        $payload = ['ib-editor' => true, 'field' => 'description', 'value' => 'Test'];

        foreach ([['field' => 'name'], ['value' => ['invalid']], ['value' => str_repeat('a', 10001)],
            ['ibaction' => 'beforeIndex'], ['toggle' => true], ['ib-editor-batch' => true]] as $invalid) {
            $this->putJson($url, array_replace($payload, $invalid))->assertUnprocessable();
        }
        $this->putJson($url, ['field' => 'description'])->assertUnprocessable();
        $this->assertSame($description, $record->fresh()->description);
        $this->assertSame('accountmanagerusers.index', $record->fresh()->name);
    }

    public function test_description_editor_requires_management_access_and_an_available_route(): void
    {
        RouteSyncHelper::sync();
        $record = $this->record('accountmanagerusers.index');
        $url = route('accountmanagerroutes.update', ['route' => $record->id]);
        $payload = ['ib-editor' => true, 'field' => 'description', 'value' => 'Test'];
        $this->actingAs($this->makeUserWithRole('editor'));
        $this->record('accountmanagerroutes.update')->roles()->attach(Role::findByName('editor'));
        $this->putJson($url, $payload)->assertForbidden();

        $this->actingAs($this->makeUserWithRole('administrator', 'admin@example.test'));
        $this->putJson('/account-management/routes/999999', $payload)->assertNotFound();
        $record->update(['is_registered' => false]);
        $this->putJson($url, $payload)->assertNotFound();
        $record->update(['is_registered' => true]);
        config(['accountmanager.routeAccess.exceptRoutes' => [$record->name]]);
        $this->putJson($url, $payload)->assertNotFound();
        config(['accountmanager.routeAccess.enabled' => false]);
        $this->putJson($url, $payload)->assertNotFound();
    }

    public function test_description_editor_requires_csrf_tokens(): void
    {
        $this->actingAs($this->makeUserWithRole('administrator'));
        RouteSyncHelper::sync();
        $record = $this->record('accountmanagerusers.index');
        $description = $record->description;
        $this->app->instance('env', 'production');
        try {
            $this->putJson(route('accountmanagerroutes.update', ['route' => $record->id]),
                ['ib-editor' => true, 'field' => 'description', 'value' => 'Test'])->assertStatus(419);
        } finally {
            $this->app->instance('env', 'testing');
        }
        $this->assertSame($description, $record->fresh()->description);
    }

    public function test_composer_path_repositories_are_resolved_through_their_install_path(): void
    {
        $file = $this->package('clients', ['clients.index' => 'Da repository locale']);
        $link = $this->directory.'/linked-package';
        symlink(dirname($file, 2), $link);
        $this->installPath('clients', $link);
        Route::get('clients', fn () => 'ok')->name('clients.index');

        RouteSyncHelper::sync();

        $this->assertSame('Da repository locale', $this->record()->description);
    }

    public function test_route_cache_preserves_action_descriptions_and_still_loads_package_descriptions(): void
    {
        $this->package('clients', ['clients.show' => 'Dettagli del cliente']);
        $router = new Router(app('events'), app());
        $router->get('clients', ['uses' => RedirectController::class, 'description' => 'Elenco dalla route'])->name('clients.index');
        $router->get('clients/{client}', RedirectController::class)->name('clients.show');
        foreach ($router->getRoutes() as $route) $route->prepareForSerialization();
        $cached = new Router(app('events'), app());
        $cached->setCompiledRoutes($router->getRoutes()->compile());

        RouteSyncHelper::sync($cached);

        $this->assertSame('Elenco dalla route', $this->record()->description);
        $this->assertSame('Dettagli del cliente', $this->record('clients.show')->description);
    }

    #[DataProvider('invalidFiles')]
    public function test_invalid_description_files_are_rejected_before_changing_the_catalog(mixed $contents): void
    {
        Route::get('clients', fn () => 'ok')->name('clients.index');
        RouteSyncHelper::sync();
        $count = CatalogRoute::where('is_registered', true)->count();
        $file = $this->package('invalid', $contents);

        try {
            RouteSyncHelper::sync();
            $this->fail('Invalid files must be rejected.');
        } catch (UnexpectedValueException $exception) {
            $this->assertStringContainsString($file, $exception->getMessage());
        }
        $this->assertSame($count, CatalogRoute::where('is_registered', true)->count());
        $this->assertNull($this->record()->description);
    }

    public static function invalidFiles(): array
    {
        return [
            'not an array' => ['invalid'],
            'nested value' => [['clients.index' => ['description' => 'Elenco']]],
            'blank description' => [['clients.index' => '  ']],
            'unnamed value' => [['Elenco']],
        ];
    }

    public function test_conflicting_package_descriptions_are_rejected_before_changing_existing_records(): void
    {
        $this->package('clients', ['clients.index' => 'Elenco clienti']);
        Route::get('clients', fn () => 'ok')->name('clients.index');
        RouteSyncHelper::sync();
        $record = $this->record();
        $this->package('conflict', ['clients.index' => 'Descrizione diversa']);

        try {
            RouteSyncHelper::sync();
            $this->fail('Conflicting package descriptions must be rejected.');
        } catch (UnexpectedValueException $exception) {
            $this->assertStringContainsString('clients.index', $exception->getMessage());
        }
        $this->assertSame('Elenco clienti', $record->fresh()->description);
        $this->assertTrue($record->fresh()->is_registered);
    }

    public function test_table_displays_an_escaped_description_beside_the_route_name(): void
    {
        Route::get('clients', fn () => 'ok')->name('clients.index');
        $description = 'Elenco clienti <script>alert("test")</script>';
        config(['route-descriptions' => ['clients.index' => $description]]);
        $this->actingAs($this->makeUserWithRole('administrator'));

        $this->get('/account-management/routes')->assertOk()->assertSee('Descrizione');
        $rows = $this->getJson('/account-management/routes', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data');
        $record = $this->record();
        $row = collect($rows)->first(fn ($row) => $row[0] === 'route-'.$record->id);
        $this->assertStringContainsString('clients.index', $row[4]);
        $this->assertStringContainsString(e($description), $row[6]);
        $this->assertStringNotContainsString('<script>', $row[6]);
        foreach ($rows as $branch) {
            if (str_starts_with($branch[0], 'branch-')) $this->assertSame('', trim($branch[6]));
        }
    }

    public function test_own_manifest_describes_the_registered_package_routes_and_respects_custom_prefixes(): void
    {
        RouteSyncHelper::sync();
        $descriptions = require __DIR__.'/../../config/route-descriptions.php';
        foreach ($descriptions as $name => $description) {
            $this->assertSame($description, $this->record($name)->description, $name);
        }
        $this->assertSame([], CatalogRoute::where('uri', 'like', 'account-management%')
            ->whereNotNull('name')->whereNull('description')->pluck('name')->all());

        config(['accountmanager.routePrefix' => 'custom.']);
        $descriptions = require __DIR__.'/../../config/route-descriptions.php';
        $this->assertArrayHasKey('custom.users.index', $descriptions);
        $this->assertArrayHasKey('accountmanager.ldap.login', $descriptions);
        $this->assertArrayNotHasKey('accountmanagerusers.index', $descriptions);
    }

    public function test_description_migration_upgrades_and_rolls_back_without_losing_route_associations(): void
    {
        RouteSyncHelper::sync();
        $record = $this->record('accountmanagerusers.index');
        $record->roles()->attach(Role::findOrCreate('reader', 'web'));
        $record->permissions()->attach(Permission::findOrCreate('users.read', 'web'));
        $migration = require __DIR__.'/../../database/migrations/2026_11_13_000001_add_description_to_accountmanager_routes_table.php';
        $migration->down();
        $this->assertFalse(Schema::hasColumn('accountmanager_routes', 'description'));
        $this->assertSame($record->id, $record->fresh()->id);
        $this->assertSame(1, $record->roles()->count());

        $migration->up();
        $this->assertNull($record->fresh()->description);
        RouteSyncHelper::sync();
        $this->assertNotNull($record->fresh()->description);
        $this->assertSame(1, $record->roles()->count());
        $this->assertSame(1, $record->permissions()->count());
    }
}

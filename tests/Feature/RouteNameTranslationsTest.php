<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\AccountManager;
use IlBronza\AccountManager\Models\Route as CatalogRoute;
use IlBronza\AccountManager\Routing\RouteNameTranslations;
use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Routing\RedirectController;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class RouteNameTranslationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (require __DIR__.'/../../database/migrations/2026_11_13_000000_create_accountmanager_routes_tables.php')->up();
        (require __DIR__.'/../../database/migrations/2026_11_13_000001_add_description_to_accountmanager_routes_table.php')->up();
        (require __DIR__.'/../../database/migrations/2026_11_13_000002_add_system_roles_to_accountmanager_routes_table.php')->up();
        config(['accountmanager.routeAccess.enabled' => true, 'accountmanager.routeAccess.exceptUris' => []]);
        $this->actingAs($this->makeUserWithRole('administrator'));
    }

    private function rows(): Collection
    {
        return collect($this->getJson('/account-management/routes', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data'));
    }

    private function row(Collection $rows, string $name): array
    {
        $id = CatalogRoute::whereName($name)->sole()->id;

        return $rows->first(fn ($row) => $row[0] === 'route-'.$id);
    }

    public function test_table_displays_existing_package_titles_beside_technical_names_and_descriptions(): void
    {
        $this->get('/account-management/routes')->assertOk()->assertSee('Nome tradotto');
        $row = $this->row($this->rows(), 'accountmanagerpermissions.index');

        $this->assertStringContainsString('accountmanagerpermissions.index', $row[4]);
        $this->assertSame('Permessi', trim($row[5]));
        $this->assertStringContainsString('value="Mostra l’elenco dei permessi disponibili."', $row[6]);
    }

    public function test_uses_each_routes_namespace_and_legacy_keys_instead_of_the_management_pages_namespace(): void
    {
        trans()->addLines(['routes.catalogitems.index' => 'Articoli di catalogo'], 'it', 'catalogue');
        trans()->addLines(['routes.clients.index' => 'Clienti dell’applicazione'], 'it');
        Route::get('labels/catalogue', ['uses' => fn () => 'ok', 'routeTranslationPrefix' => 'catalogue::routes.'])
            ->name('catalog.items.index');
        Route::get('labels/role', ['uses' => fn () => 'ok', 'ibPackage' => AccountManager::class])
            ->name('accountmanager.roles.show');
        Route::get('labels/roles', ['uses' => fn () => 'ok',
            'routeTranslationPrefix' => ['unused::routes.', 'accountmanager::routes.']])->name('accountmanager.roles.index');
        Route::get('labels/clients', fn () => 'ok')->name('clients.index');

        $rows = $this->rows();

        $this->assertSame('Articoli di catalogo', trim($this->row($rows, 'catalog.items.index')[5]));
        $this->assertSame('Mostra ruolo', trim($this->row($rows, 'accountmanager.roles.show')[5]));
        $this->assertSame('Ruoli', trim($this->row($rows, 'accountmanager.roles.index')[5]));
        $this->assertSame('Clienti dell’applicazione', trim($this->row($rows, 'clients.index')[5]));
    }

    public function test_titles_follow_the_current_locale_and_laravels_fallback_locale(): void
    {
        $this->assertSame('Permessi', trim($this->row($this->rows(), 'accountmanagerpermissions.index')[5]));
        app()->setLocale('en');
        $this->assertSame('Permissions', trim($this->row($this->rows(), 'accountmanagerpermissions.index')[5]));
        app()->setLocale('fr');
        trans()->setFallback('en');
        $this->assertSame('Permissions', trim($this->row($this->rows(), 'accountmanagerpermissions.index')[5]));
    }

    public function test_missing_titles_unnamed_routes_and_branches_are_empty_and_translations_are_escaped(): void
    {
        $title = 'Clienti <script>alert("test")</script>';
        trans()->addLines(['routes.clients.index' => $title], 'it');
        Route::get('labels/clients', fn () => 'ok')->name('clients.index');
        Route::get('labels/missing-package', ['uses' => fn () => 'ok', 'routeTranslationPrefix' => 'accountmanager::routes.'])
            ->name('missing.package');
        Route::get('labels/missing-host', fn () => 'ok')->name('missing.host');
        Route::get('labels/group', ['uses' => fn () => 'ok', 'routeTranslationPrefix' => 'accountmanager::routes.'])
            ->name('accountmanagerroles');
        Route::get('labels/unnamed', fn () => 'ok');

        $rows = $this->rows();

        $this->assertSame(e($title), trim($this->row($rows, 'clients.index')[5]));
        foreach (['missing.package', 'missing.host', 'accountmanagerroles'] as $name) {
            $this->assertSame('', trim($this->row($rows, $name)[5]));
        }
        $unnamed = CatalogRoute::whereUri('labels/unnamed')->sole();
        $unnamedRow = $rows->first(fn ($row) => $row[0] === 'route-'.$unnamed->id);
        $this->assertSame('', trim($unnamedRow[5]));
        foreach ($rows as $row) {
            if (str_starts_with($row[0], 'branch-')) $this->assertSame('', trim($row[5]));
        }
    }

    public function test_route_cache_retains_the_package_translation_metadata(): void
    {
        $router = new Router(app('events'), app());
        $router->get('roles', ['uses' => RedirectController::class, 'routeTranslationPrefix' => 'accountmanager::routes.'])
            ->name('accountmanagerroles.index');
        foreach ($router->getRoutes() as $route) $route->prepareForSerialization();
        $cached = new Router(app('events'), app());
        $cached->setCompiledRoutes($router->getRoutes()->compile());

        $this->assertSame('Ruoli', (new RouteNameTranslations($cached))->forName('accountmanagerroles.index'));
    }
}

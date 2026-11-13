<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Helpers\RouteSyncHelper;
use IlBronza\AccountManager\Models\Permission;
use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Models\Route as CatalogRoute;
use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Support\Facades\Route;

class RoutePreviewTest extends TestCase
{
    private const PREVIEW = '/account-management/routes/preview';

    protected function setUp(): void
    {
        parent::setUp();
        (require __DIR__.'/../../database/migrations/2026_11_13_000000_create_accountmanager_routes_tables.php')->up();
        (require __DIR__.'/../../database/migrations/2026_11_13_000001_add_description_to_accountmanager_routes_table.php')->up();
        (require __DIR__.'/../../database/migrations/2026_11_13_000002_add_system_roles_to_accountmanager_routes_table.php')->up();
        config(['accountmanager.routeAccess.enabled' => true]);
        $this->actingAs($this->makeUserWithRole('administrator'));
    }

    private function preview(string $name)
    {
        return $this->post(self::PREVIEW, ['route' => CatalogRoute::whereName($name)->sole()->getKey()]);
    }

    public function test_post_opens_a_real_crud_page_with_a_database_record_and_the_current_session(): void
    {
        config(['accountmanager.routeAccess.exceptUris' => []]);
        $permission = Permission::findOrCreate('read-example', 'web');
        $rows = $this->getJson('/account-management/routes', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data');
        $show = CatalogRoute::whereName('accountmanagerpermissions.show')->sole();
        $row = collect($rows)->first(fn ($row) => $row[0] === 'route-'.$show->id);
        $this->assertNotNull($row);
        $this->assertSame([
            route('accountmanagerroutes.preview', ['route' => $show->id]),
            __('accountmanager::route-access.preview'),
        ], $row[9]);
        $response = $this->preview($show->name)->assertStatus(303)
            ->assertRedirect('/account-management/permissions/'.$permission->id.'?iframed=true');
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('read-example');
        $this->assertDatabaseCount('accountmanager_route_role', 0);
    }

    public function test_samples_scoped_children_from_a_parent_that_has_one_and_uses_the_custom_key(): void
    {
        Route::middleware('web')->get('samples/{role}/permissions/{permission:name}',
            fn (Role $role, Permission $permission) => $role->name.' / '.$permission->name)->scopeBindings()->name('samples.show');
        Permission::findOrCreate('unrelated', 'web');
        $role = Role::findOrCreate('reader', 'web');
        $role->givePermissionTo(Permission::findOrCreate('view pages', 'web'));
        RouteSyncHelper::sync();

        $response = $this->preview('samples.show')->assertStatus(303)
            ->assertRedirect('/samples/'.$role->id.'/permissions/view%20pages?iframed=true');
        $this->get($response->headers->get('Location'))->assertOk()->assertSeeText('reader / view pages');
    }

    public function test_resolves_scalar_crud_keys_and_omits_optional_parameters(): void
    {
        Route::get('sample/users/{id}/edit', fn ($id) => (string) $id)->name('samples.edit');
        Route::get('sample/overview/{period?}', fn () => 'overview')->name('samples.index');
        RouteSyncHelper::sync();
        $this->preview('samples.edit')->assertRedirect('/sample/users/'.auth()->id().'/edit?iframed=true');
        $this->preview('samples.index')->assertRedirect('/sample/overview?iframed=true');
    }

    public function test_recognizes_an_unnamed_laravel_view_and_retains_the_current_domain(): void
    {
        Route::domain('{tenant}.example.test')->group(function () {
            Route::view('overview', 'accountmanager::auth.logout');
        });
        RouteSyncHelper::sync();
        $record = CatalogRoute::where('uri', 'overview')->whereNull('name')->sole();
        $this->post('http://tenant1.example.test'.self::PREVIEW, ['route' => $record->id])
            ->assertStatus(303)->assertRedirect('http://tenant1.example.test/overview?iframed=true');
    }

    public function test_unavailable_examples_stay_in_the_iframe_and_unknown_actions_have_no_button(): void
    {
        Route::get('sample/{unresolvable}', fn () => 'no example')->name('unknown.show');
        Route::get('sample-action', fn () => throw new \LogicException('Executed a GET action'))->name('samples.duplicate');
        Route::post('sample-write', fn () => throw new \LogicException('Executed a write'))->name('write.show');
        Route::get('sample-view/read', fn () => 'some other action')->name('other.action');
        Route::get('sample-view/{permission:name}', fn (Permission $permission) => $permission->name)->name('sample.show');
        Route::domain('other.test')->get('sample', fn () => 'other')->name('other.show');
        $rows = $this->getJson('/account-management/routes', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->json('data');
        $previewUrls = array_column(array_column($rows, 9), 0);

        foreach (['unknown.show', 'samples.duplicate', 'write.show', 'other.show', 'accountmanagerroutes.index'] as $name) {
            $id = CatalogRoute::whereName($name)->sole()->id;
            $this->assertNotContains(route('accountmanagerroutes.preview', ['route' => $id]), $previewUrls);
            $this->preview($name)->assertUnprocessable()->assertSeeText($name);
        }
        $this->preview('sample.show')->assertUnprocessable()->assertSeeText('Nessun esempio');
        Permission::findOrCreate('read', 'web');
        $this->preview('sample.show')->assertUnprocessable();
        Permission::findOrCreate('available', 'web');
        $this->preview('sample.show')->assertRedirect('/sample-view/available?iframed=true');
    }

    public function test_preview_respects_management_gate_csrf_catalog_and_destination_middleware(): void
    {
        Route::middleware(['web', 'role:reader'])->get('read-only', fn () => 'restricted')->name('restricted.index');
        RouteSyncHelper::sync();
        $url = $this->preview('restricted.index')->assertStatus(303)->headers->get('Location');
        $this->get($url)->assertForbidden();
        $this->post(self::PREVIEW, ['route' => 'https://external.test'])->assertNotFound();
        $this->post(self::PREVIEW.'?route=1')->assertNotFound();
        CatalogRoute::whereName('restricted.index')->update(['is_registered' => false]);
        $this->preview('restricted.index')->assertNotFound();

        $this->app->instance('env', 'production');
        try {
            $this->post(self::PREVIEW, ['route' => 1])->assertStatus(419);
        } finally {
            $this->app->instance('env', 'testing');
        }
        config(['accountmanager.routeAccess.enabled' => false]);
        $this->post(self::PREVIEW)->assertNotFound();
        config(['accountmanager.routeAccess.enabled' => true]);
        $this->actingAs($this->makeUserWithRole('reader', 'reader@example.test'));
        CatalogRoute::whereName('accountmanagerroutes.preview')->sole()->roles()->attach(Role::findByName('reader'));
        $this->post(self::PREVIEW)->assertForbidden();
    }
}

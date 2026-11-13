<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrationsTest extends TestCase
{
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../installation');
    }

    public function test_install_rollback_and_reinstall_preserve_existing_users_and_roles(): void
    {
        config(['accountmanager.logUserAccess.connection' => null]);
        DB::table('users')->insert(['name' => 'Existing user', 'email' => 'existing@example.test', 'password' => bcrypt('unique-password')]);
        DB::table('roles')->insert(['name' => 'custom-role', 'guard_name' => 'web']);
        $path = realpath(__DIR__ . '/../../database/migrations');
        $this->artisan('migrate', ['--path' => [$path], '--realpath' => true])->assertSuccessful();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('roles', ['name' => 'custom-role']);
        $this->assertTrue(Schema::hasTable('user_access_logs'));
        $this->assertTrue(Schema::hasTable('accountmanager_routes'));
        $this->assertTrue(Schema::hasColumn('accountmanager_routes', 'description'));
        $this->assertTrue(Schema::hasColumn('accountmanager_routes', 'system_roles'));
        $this->assertTrue(Schema::hasTable('accountmanager_route_role'));
        $this->assertTrue(Schema::hasTable('accountmanager_route_permission'));
        $this->assertTrue(Schema::hasColumn('users', 'github_id'));
        $rolesCount = DB::table('roles')->count();
        $this->artisan('migrate:rollback', ['--path' => [$path], '--realpath' => true])->assertSuccessful();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('roles', ['name' => 'custom-role']);
        $this->assertFalse(Schema::hasTable('user_access_logs'));
        $this->assertFalse(Schema::hasTable('accountmanager_routes'));
        $this->assertFalse(Schema::hasColumn('users', 'active'));
        $this->artisan('migrate', ['--path' => [$path], '--realpath' => true])->assertSuccessful();
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('roles', $rolesCount);
    }

    public function test_empty_installation_does_not_create_an_administrator(): void
    {
        $this->artisan('migrate', [
            '--path' => [realpath(__DIR__ . '/../../database/migrations')], '--realpath' => true,
        ])->assertSuccessful();
        $this->assertDatabaseCount('users', 0);
    }
}

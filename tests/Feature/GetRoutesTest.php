<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Models\Permission;
use IlBronza\AccountManager\Models\Role;
use IlBronza\AccountManager\Models\UserAccessLog;
use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionFunction;

class GetRoutesTest extends TestCase
{
	public static function pageRoutes(): array
	{
		return [
			'account edit' => ['accountmanageraccountmanager.account', 'account-management/edit-account'],
			'access logs' => ['accountmanageraccessLogs.index', 'account-management/access-logs'],
			'user avatar edit' => ['accountmanageraccountmanager.user.editAvatar', 'account-management/userdata/{user}/edit-avatar'],
			'own userdata edit' => ['accountmanageraccountmanager.editUserdata', 'account-management/userdata/edit'],
			'own avatar edit' => ['accountmanageraccountmanager.editAvatar', 'account-management/userdata/edit-avatar'],
			'roles index' => ['accountmanagerroles.index', 'account-management/roles'],
			'roles create' => ['accountmanagerroles.create', 'account-management/roles/create'],
			'roles show' => ['accountmanagerroles.show', 'account-management/roles/{role}'],
			'roles edit' => ['accountmanagerroles.edit', 'account-management/roles/{role}/edit'],
			'permissions index' => ['accountmanagerpermissions.index', 'account-management/permissions'],
			'permissions create' => ['accountmanagerpermissions.create', 'account-management/permissions/create'],
			'permissions show' => ['accountmanagerpermissions.show', 'account-management/permissions/{permission}'],
			'permissions edit' => ['accountmanagerpermissions.edit', 'account-management/permissions/{permission}/edit'],
			'users trashed' => ['accountmanagerusers.trashed', 'account-management/users/trashed'],
			'users create slim' => ['accountmanagerusers.createSlim', 'account-management/users/create-slim'],
			'users index' => ['accountmanagerusers.index', 'account-management/users'],
			'users create' => ['accountmanagerusers.create', 'account-management/users/create'],
			'users show' => ['accountmanagerusers.show', 'account-management/users/{user}'],
			'users edit' => ['accountmanagerusers.edit', 'account-management/users/{user}/edit'],
			'userdata show' => ['accountmanageruserdatas.show', 'account-management/userdata/{userdata}'],
			'userdata edit' => ['accountmanageruserdatas.edit', 'account-management/userdata/{userdata}/edit'],
		];
	}

	public static function protectedRoutes(): array
	{
        return self::pageRoutes();
    }


	public static function managementRoutes(): array
	{
		$routes = self::protectedRoutes();
		unset($routes['account edit']);
		return $routes;
	}

	#[DataProvider('pageRoutes')]
	public function test_get_page_renders_for_superadmin(string $name, string $uri): void
	{
		$admin = $this->makeUserWithRole('superadmin');
		$this->actingAs($admin);
		$target = $this->makeUserWithRole('editor', 'target@example.test');
		$target->getUserdata()->forceFill(['first_name' => 'Mario', 'surname' => 'Rossi', 'short_name' => 'MR'])->save();
		$role = Role::findByName('editor');
		$permission = Permission::findOrCreate('test.read', 'web');
		$role->givePermissionTo($permission);
		$trashed = $this->makeUserWithRole('editor', 'trashed@example.test');
		$trashed->delete();
		UserAccessLog::create([
			'user_id' => $target->getKey(),
			'visited_at' => now(),
			'method' => 'GET',
			'url' => 'http://localhost/account-management/users',
			'ip' => '127.0.0.1',
		]);

		$path = strtr($uri, [
			'{user}' => $target->getKey(),
			'{userdata}' => $target->getKey(),
			'{role}' => $role->getKey(),
			'{permission}' => $permission->getKey(),
		]);
		$this->assertSame($uri, Route::getRoutes()->getByName($name)?->uri());
		$this->get($path)->assertOk()->assertSee('<html', false);
	}

	#[DataProvider('protectedRoutes')]
	public function test_get_route_requires_authentication(string $name, string $uri): void
	{
		$path = strtr($uri, ['{user}' => 999, '{userdata}' => 999, '{role}' => 999, '{permission}' => 999]);
		$this->getJson($path)->assertUnauthorized();
	}

	#[DataProvider('managementRoutes')]
	public function test_management_get_route_denies_unconfigured_role(string $name, string $uri): void
	{
		$user = $this->makeUserWithRole('editor');
		$this->actingAs($user);
		$user->getUserdata();
		$path = strtr($uri, [
			'{user}' => $user->getKey(),
			'{userdata}' => $user->getKey(),
			'{role}' => Role::findByName('editor')->getKey(),
			'{permission}' => Permission::findOrCreate('test.read', 'web')->getKey(),
		]);
		$this->getJson($path)->assertForbidden();
	}


	public function test_every_registered_package_get_route_has_a_test(): void
	{
		$expected = array_column(self::protectedRoutes(), 0, 1) + [
			'auth/redirect' => 'login.github',
			'login/ldap' => 'accountmanager.ldap.login',
            'account-management/ldap-users' => 'accountmanagerldapUsers.index',
			'auth/callback' => null,
			'logout' => 'accountmanageraccountmanager.logout.confirm',
		];
		$actual = [];
		$file = realpath(__DIR__ . '/../../routes/accountmanager.php');
		foreach (Route::getRoutes() as $route) {
			if (! in_array('GET', $route->methods(), true)) {
				continue;
			}
			$controller = $route->getAction('controller');
			$closure = $route->getAction('uses');
			$isPackageRoute = is_string($controller) && str_starts_with($controller, 'IlBronza\\AccountManager\\');
			if ($closure instanceof \Closure) {
				$isPackageRoute = (new ReflectionFunction($closure))->getFileName() === $file;
			}
			if ($isPackageRoute) {
				$actual[$route->uri()] = $route->getName();
			}
		}
		ksort($actual);
		ksort($expected);
		$this->assertSame($expected, $actual, 'Update the GET route tests when adding or changing a package route.');
	}
    public function test_unimplemented_resource_routes_are_not_registered(): void
    {
        foreach (['roles.archived', 'roles.list', 'permissions.archived', 'permissions.list'] as $name) {
            $this->assertFalse(Route::has('accountmanager' . $name));
        }
    }
}

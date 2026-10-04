<?php

namespace IlBronza\AccountManager\Tests\Feature;

use IlBronza\AccountManager\Models\Permission;
use IlBronza\AccountManager\Tests\TestCase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as GithubUser;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;

class SessionGetRoutesTest extends TestCase
{
	public function test_github_redirect_uses_the_provider_authorization_url(): void
	{
		$provider = Mockery::mock(Provider::class);
		$provider->shouldReceive('redirect')->once()->andReturn(redirect('https://github.com/login/oauth/authorize?state=test'));
		Socialite::shouldReceive('driver')->once()->with('github')->andReturn($provider);

		$this->get('/auth/redirect')->assertRedirect('https://github.com/login/oauth/authorize?state=test');
	}

	public static function githubUserMatches(): array
	{
		return [
			'GitHub ID' => [true],
			'email' => [false],
		];
	}

	#[DataProvider('githubUserMatches')]
	public function test_github_callback_logs_in_an_existing_user(bool $matchByGithubId): void
	{
		$user = $this->makeUserWithRole('editor');
		if ($matchByGithubId) {
			$user->github_id = 'github-42';
			$user->save();
		}
		$github = (new GithubUser())->map([
			'id' => 'github-42',
			'name' => 'GitHub Tester',
			'email' => $matchByGithubId ? 'changed@example.test' : $user->email,
		]);
		$github->token = 'test-access-token';
		$github->refreshToken = 'test-refresh-token';
		$provider = Mockery::mock(Provider::class);
		$provider->shouldReceive('user')->once()->andReturn($github);
		Socialite::shouldReceive('driver')->once()->with('github')->andReturn($provider);

		$this->get('/auth/callback')->assertRedirect('/home');
		$this->assertAuthenticatedAs($user);
		$this->assertDatabaseCount('users', 1);
		$this->assertDatabaseHas('users', [
			'id' => $user->getKey(),
			'github_token' => 'test-access-token',
			'github_refresh_token' => 'test-refresh-token',
		]);
	}

	public function test_github_callback_creates_and_logs_in_a_new_user(): void
	{
		$github = (new GithubUser())->map([
			'id' => 'github-new',
			'name' => 'New GitHub User',
			'email' => 'new@example.test',
		]);
		$github->token = 'new-access-token';
		$github->refreshToken = 'new-refresh-token';
		$provider = Mockery::mock(Provider::class);
		$provider->shouldReceive('user')->once()->andReturn($github);
		Socialite::shouldReceive('driver')->once()->with('github')->andReturn($provider);

		$this->get('/auth/callback')->assertRedirect('/home');
		$this->assertAuthenticated();
		$this->assertDatabaseCount('users', 1);
		$this->assertDatabaseHas('users', [
			'name' => 'New GitHub User',
			'email' => 'new@example.test',
			'active' => true,
			'github_token' => 'new-access-token',
			'github_refresh_token' => 'new-refresh-token',
		]);
	}

	public function test_logout_ends_the_session(): void
	{
		$this->actingAs($this->makeUserWithRole('editor'));
		$this->withSession(['private_value' => 'remove-me']);

		$this->get('/logout')->assertRedirect('/')->assertSessionMissing('private_value');
		$this->assertGuest();
	}

	public function test_duplicate_user_redirects_back_and_copies_access_rights(): void
	{
		$admin = $this->makeUserWithRole('superadmin');
		$this->actingAs($admin);
		$source = $this->makeUserWithRole('editor', 'source@example.test');
		$permission = Permission::findOrCreate('test.read', 'web');
		$source->givePermissionTo($permission);

		$this->from('/account-management/users')
			->get('/account-management/users/duplicate/' . $source->getKey())
			->assertRedirect('/account-management/users');

		$this->assertDatabaseCount('users', 3);
		$clone = $source::where('email', 'copy_' . $source->email)->firstOrFail();
		$this->assertSame('copy_' . $source->name, $clone->name);
		$this->assertSame($source->getRoleNames()->all(), $clone->getRoleNames()->all());
		$this->assertTrue($clone->hasDirectPermission($permission));
		$this->assertAuthenticatedAs($admin);
	}
}

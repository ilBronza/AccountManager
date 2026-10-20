<?php

namespace IlBronza\AccountManager\Tests\Feature;

use App\Models\ProjectSpecific\User;
use IlBronza\AccountManager\Tests\TestCase;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Laravel\Ui\UiServiceProvider;
use PHPUnit\Framework\Attributes\DataProvider;

class PasswordRecoveryTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return array_merge(parent::getPackageProviders($app), [UiServiceProvider::class]);
    }

    protected function defineRoutes($router)
    {
        // These App controllers are published scaffolding: exercise the exact
        // package sources, without maintaining test copies of their behavior.
        foreach (['LoginController', 'ForgotPasswordController', 'ResetPasswordController'] as $controller) {
            require_once __DIR__ . '/../../src/Http/Controllers/Auth/' . $controller . '.php';
        }

        // Resolve auth.* as an application's published views, including UIkit.
        app('view')->getFinder()->prependLocation(__DIR__ . '/../../resources/views');
        Route::middleware('web')->group(function () {
            Auth::routes(['register' => false]);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_forgot_password_form_uses_laravel_ui_routes_and_package_layout(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertViewIs('auth.forgot-password')
            ->assertSee('<html', false)
            ->assertSee('forgot-password')
            ->assertSee('action="' . route('password.email') . '"', false)
            ->assertSee('name="_token"', false);

        $this->assertFalse(Route::has('password.store'));
        $this->assertFalse(Route::has('register'));
    }

    public function test_link_request_sends_notification_and_displays_success(): void
    {
        $user = $this->makeUserWithRole('editor');
        $this->requestResetLink($user);

        Notification::assertSentToTimes($user, ResetPassword::class, 1);
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee(trans(Password::RESET_LINK_SENT));
        $this->assertGuest();
    }

    public function test_notification_link_opens_reset_form_with_token_and_encoded_email(): void
    {
        $user = $this->makeUserWithRole('editor', 'tester+reset@example.test');
        $token = $this->requestResetLink($user);
        $notification = Notification::sent($user, ResetPassword::class)->sole();
        $url = $notification->toMail($user)->actionUrl;

        $this->assertSame(route('password.reset', ['token' => $token, 'email' => $user->email]), $url);
        $this->get($url)
            ->assertOk()
            ->assertViewIs('auth.reset-password')
            ->assertViewHas('token', $token)
            ->assertViewHas('email', $user->email)
            ->assertSee('action="' . route('password.update') . '"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="token" value="' . $token . '"', false)
            ->assertSee('value="' . e($user->email) . '"', false);
    }

    public function test_reset_form_can_open_without_an_email_query_parameter(): void
    {
        $this->get(route('password.reset', ['token' => 'example-token']))
            ->assertOk()
            ->assertViewHas('token', 'example-token')
            ->assertViewHas('email', null);
    }

    public function test_valid_token_updates_password_logs_in_and_cannot_be_reused(): void
    {
        Event::fake([PasswordResetEvent::class]);
        $user = $this->makeUserWithRole('editor');
        $oldHash = $user->getRawOriginal('password');
        $oldRememberToken = $user->getRememberToken();
        $token = $this->requestResetLink($user);
        $payload = $this->resetPayload($user, $token);

        $this->post(route('password.update'), $payload)
            ->assertRedirect('/home')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', trans(Password::PASSWORD_RESET));

        $user->refresh();
        $this->assertNotSame($oldHash, $user->getRawOriginal('password'));
        $this->assertTrue(Hash::check($payload['password'], $user->password));
        $this->assertFalse(Hash::check('password', $user->password));
        $this->assertNotSame($oldRememberToken, $user->getRememberToken());
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        Event::assertDispatched(PasswordResetEvent::class, fn ($event) => $event->user->is($user));

        Auth::logout();
        $this->from(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->post(route('password.update'), array_replace($payload, [
                'password' => 'Another-password-456!',
                'password_confirmation' => 'Another-password-456!',
            ]))
            ->assertSessionHasErrors(['email' => trans(Password::INVALID_TOKEN)]);
        $this->assertTrue(Hash::check($payload['password'], $user->fresh()->password));
        $this->assertGuest();
    }

    public static function invalidLinkRequests(): array
    {
        return [
            'missing email' => [[]],
            'malformed email' => [['email' => 'not-an-email']],
            'unknown email' => [['email' => 'unknown@example.test']],
        ];
    }

    #[DataProvider('invalidLinkRequests')]
    public function test_invalid_link_requests_show_errors_without_sending_notifications(array $payload): void
    {
        $this->from(route('password.request'))->post(route('password.email'), $payload)
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');

        $message = session('errors')->first('email');
        $this->assertNotEmpty($message);
        $this->get(route('password.request'))->assertOk()->assertSee($message);
        $this->assertDatabaseCount('password_reset_tokens', 0);
        Notification::assertNothingSent();
    }

    public function test_repeated_link_requests_are_throttled(): void
    {
        $user = $this->makeUserWithRole('editor');
        $this->requestResetLink($user);

        $this->from(route('password.request'))->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors(['email' => trans(Password::RESET_THROTTLED)]);

        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public static function invalidTokens(): array
    {
        return [
            'incorrect token' => ['incorrect'],
            'expired token' => ['expired'],
            'token belongs to another user' => ['another-user'],
        ];
    }

    #[DataProvider('invalidTokens')]
    public function test_invalid_tokens_do_not_change_password_and_show_broker_error(string $case): void
    {
        Event::fake([PasswordResetEvent::class]);
        $user = $this->makeUserWithRole('editor');
        $token = $this->requestResetLink($user);
        $hash = $user->getRawOriginal('password');
        $payload = $this->resetPayload($user, $token);

        if ($case === 'incorrect') {
            $payload['token'] = 'invalid-token';
        } elseif ($case === 'expired') {
            DB::table('password_reset_tokens')->where('email', $user->email)->update([
                'created_at' => now()->subMinutes(config('auth.passwords.users.expire') + 1),
            ]);
        } else {
            $payload['email'] = $this->makeUserWithRole('editor', 'other@example.test')->email;
        }

        $url = route('password.reset', ['token' => $token, 'email' => $payload['email']]);
        $this->from($url)->post(route('password.update'), $payload)
            ->assertRedirect($url)
            ->assertSessionHasErrors(['email' => trans(Password::INVALID_TOKEN)])
            ->assertSessionHasInput('email', $payload['email']);
        $this->get($url)->assertOk()->assertSee(trans(Password::INVALID_TOKEN));

        $this->assertSame($hash, $user->fresh()->getRawOriginal('password'));
        if ($case === 'another-user') {
            $this->assertTrue(Hash::check('password', User::where('email', $payload['email'])->firstOrFail()->password));
        }
        $this->assertGuest();
        Event::assertNotDispatched(PasswordResetEvent::class);
    }

    public static function invalidResetFields(): array
    {
        return [
            'missing token' => [['token' => ''], 'token'],
            'missing email' => [['email' => ''], 'email'],
            'malformed email' => [['email' => 'invalid'], 'email'],
            'missing password' => [['password' => '', 'password_confirmation' => ''], 'password'],
            'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
            'missing confirmation' => [['password_confirmation' => ''], 'password'],
            'different confirmation' => [['password_confirmation' => 'different-password'], 'password'],
        ];
    }

    #[DataProvider('invalidResetFields')]
    public function test_reset_validates_fields_before_changing_password(array $invalid, string $field): void
    {
        $user = $this->makeUserWithRole('editor');
        $token = $this->requestResetLink($user);
        $hash = $user->getRawOriginal('password');
        $url = route('password.reset', ['token' => $token, 'email' => $user->email]);

        $this->from($url)->post(route('password.update'), array_replace($this->resetPayload($user, $token), $invalid))
            ->assertRedirect($url)
            ->assertSessionHasErrors($field);
        $message = session('errors')->first($field);
        $this->assertNotEmpty($message);
        $this->get($url)->assertOk()->assertSee($message);

        $this->assertSame($hash, $user->fresh()->getRawOriginal('password'));
        $this->assertTrue(Password::broker()->tokenExists($user, $token));
        $this->assertGuest();
    }

    private function requestResetLink(User $user): string
    {
        $this->from(route('password.request'))->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', trans(Password::RESET_LINK_SENT));

        Notification::assertSentTo($user, ResetPassword::class);
        $token = Notification::sent($user, ResetPassword::class)->sole()->token;
        $this->assertTrue(Password::broker()->tokenExists($user, $token));
        $this->assertNotSame($token, DB::table('password_reset_tokens')->where('email', $user->email)->value('token'));

        return $token;
    }

    private function resetPayload(User $user, string $token): array
    {
        return [
            'token' => $token,
            'email' => $user->email,
            'password' => 'New-password-123!',
            'password_confirmation' => 'New-password-123!',
        ];
    }
}

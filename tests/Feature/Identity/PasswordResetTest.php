<?php

declare(strict_types=1);

use App\Modules\Identity\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\postJson;

it('sends a reset link to the frontend and resets the password with the token', function () {
    Notification::fake();
    config(['identity.frontend_url' => 'https://app.example.com']);
    $user = User::factory()->create();

    postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk();

    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token, $user): bool {
        $token = $notification->token;
        $mail = $notification->toMail($user);

        return $mail instanceof MailMessage
            && is_string($mail->actionUrl)
            && str_starts_with($mail->actionUrl, 'https://app.example.com/reset-password?token=');
    });

    postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'N3w-Str0ng-Passw0rd!',
        'password_confirmation' => 'N3w-Str0ng-Passw0rd!',
    ])->assertOk();

    expect(Hash::check('N3w-Str0ng-Passw0rd!', $user->refresh()->password))->toBeTrue();
});

it('does not reveal whether an email is registered', function () {
    Notification::fake();

    postJson('/api/auth/forgot-password', ['email' => 'nobody@example.com'])
        ->assertOk()
        ->assertJsonPath('message', 'If the account exists, a reset link has been sent.');

    Notification::assertNothingSent();
});

it('logs the user out of every device after a reset', function () {
    Notification::fake();
    $user = User::factory()->create();
    $phone = apiLogin($user->email);
    $laptop = apiLogin($user->email);

    postJson('/api/auth/forgot-password', ['email' => $user->email]);
    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
        $token = $notification->token;

        return true;
    });

    postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'N3w-Str0ng-Passw0rd!',
        'password_confirmation' => 'N3w-Str0ng-Passw0rd!',
    ])->assertOk();

    postJson('/api/auth/refresh', ['refresh_token' => $phone['refresh_token']])->assertUnauthorized();
    postJson('/api/auth/refresh', ['refresh_token' => $laptop['refresh_token']])->assertUnauthorized();
});

it('rejects an invalid reset token', function () {
    $user = User::factory()->create();

    postJson('/api/auth/reset-password', [
        'token' => 'invalid',
        'email' => $user->email,
        'password' => 'N3w-Str0ng-Passw0rd!',
        'password_confirmation' => 'N3w-Str0ng-Passw0rd!',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

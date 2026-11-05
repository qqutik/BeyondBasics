<?php

declare(strict_types=1);

use App\Modules\Identity\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

function verificationUrl(User $user, ?string $email = null): string
{
    return URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id,
        'hash' => sha1($email ?? $user->email),
    ]);
}

it('verifies the email through the signed link without a token', function () {
    $user = User::factory()->unverified()->create();

    getJson(verificationUrl($user))->assertOk();

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects a link with a wrong hash', function () {
    $user = User::factory()->unverified()->create();

    getJson(verificationUrl($user, 'someone-else@example.com'))->assertForbidden();

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects a tampered signature', function () {
    $user = User::factory()->unverified()->create();

    getJson(verificationUrl($user).'tampered')->assertForbidden();

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('resends the verification email to an unverified user', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();
    $tokens = apiLogin($user->email);

    postJson('/api/auth/email/resend', [], bearer($tokens['access_token']))->assertAccepted();

    Notification::assertSentTo($user, VerifyEmail::class);
});

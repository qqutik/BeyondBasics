<?php

declare(strict_types=1);

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Events\RoleGrantedEvent;
use App\Modules\Identity\Events\RoleRevokedEvent;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Models\UserRole;
use App\Modules\Identity\Services\RoleService;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\assertDatabaseHas;

it('grants a role once and records who granted it', function () {
    Event::fake([RoleGrantedEvent::class]);
    $admin = User::factory()->create();
    $user = User::factory()->create();

    expect(app(RoleService::class)->grant($user, Role::AUTHOR, $admin))->toBeTrue()
        ->and(app(RoleService::class)->grant($user, Role::AUTHOR, $admin))->toBeFalse()
        ->and($user->hasRole(Role::AUTHOR))->toBeTrue();

    expect(UserRole::query()->where('user_id', $user->id)->count())->toBe(1);
    assertDatabaseHas('user_roles', ['user_id' => $user->id, 'role' => 'author', 'granted_by' => $admin->id]);
    Event::assertDispatchedTimes(RoleGrantedEvent::class, 1);
});

it('records system grants with no actor', function () {
    $user = User::factory()->create();

    app(RoleService::class)->grant($user, Role::STUDENT);

    assertDatabaseHas('user_roles', ['user_id' => $user->id, 'role' => 'student', 'granted_by' => null]);
});

it('keeps several roles per user', function () {
    $user = User::factory()->create();

    app(RoleService::class)->grant($user, Role::STUDENT);
    app(RoleService::class)->grant($user, Role::AUTHOR);

    expect($user->hasRole(Role::STUDENT))->toBeTrue()
        ->and($user->hasRole(Role::AUTHOR))->toBeTrue()
        ->and($user->hasRole(Role::ADMIN))->toBeFalse();
});

it('revokes a role', function () {
    Event::fake([RoleRevokedEvent::class]);
    $user = User::factory()->create();
    app(RoleService::class)->grant($user, Role::AUTHOR);

    expect(app(RoleService::class)->revoke($user, Role::AUTHOR))->toBeTrue()
        ->and(app(RoleService::class)->revoke($user, Role::AUTHOR))->toBeFalse()
        ->and($user->hasRole(Role::AUTHOR))->toBeFalse();

    Event::assertDispatchedTimes(RoleRevokedEvent::class, 1);
});

it('keeps the granted role when the granting admin is deleted', function () {
    $admin = User::factory()->create();
    $user = User::factory()->create();
    app(RoleService::class)->grant($user, Role::AUTHOR, $admin);

    $admin->forceDelete();

    assertDatabaseHas('user_roles', ['user_id' => $user->id, 'role' => 'author', 'granted_by' => null]);
});

it('deletes only its own row when a single UserRole model is deleted', function () {
    $user = User::factory()->create();
    app(RoleService::class)->grant($user, Role::STUDENT);
    app(RoleService::class)->grant($user, Role::AUTHOR);

    UserRole::query()->where('user_id', $user->id)->where('role', Role::AUTHOR->value)->sole()->delete();

    expect(UserRole::query()->where('user_id', $user->id)->pluck('role')->all())->toBe([Role::STUDENT]);
});

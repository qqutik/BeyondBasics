<?php

declare(strict_types=1);

use App\Modules\Identity\DTO\Auth\ClientInfoDTO;
use App\Modules\Identity\DTO\Auth\TokenPairDTO;
use App\Modules\Identity\DTO\BaseDTO;
use App\Modules\Identity\DTO\User\UserCreateDTO;
use App\Modules\Identity\Enums\Role;

it('includes private properties of the concrete DTO', function () {
    expect((new UserCreateDTO('Ada', 'ada@example.com', 'secret'))->toArray())
        ->toBe(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'secret']);
});

it('reflects changes made through setters', function () {
    $dto = (new UserCreateDTO('Ada', 'ada@example.com', 'secret'))->setName('Grace');

    expect($dto->toArray()['name'])->toBe('Grace');
});

it('keys properties in snake_case, including ones set in the constructor body', function () {
    expect((new TokenPairDTO('access', 900, 'refresh', 86400))->toArray())->toBe([
        'token_type' => 'Bearer',
        'access_token' => 'access',
        'expires_in' => 900,
        'refresh_token' => 'refresh',
        'refresh_expires_in' => 86400,
    ]);
});

it('keeps null values', function () {
    expect((new ClientInfoDTO(null, 'curl/8'))->toArray())->toBe(['ip_address' => null, 'user_agent' => 'curl/8']);
});

it('converts nested DTOs, arrays of DTOs and backed enums', function () {
    $dto = new class(new ClientInfoDTO('127.0.0.1', null), [new ClientInfoDTO(null, 'a')], Role::AUTHOR) extends BaseDTO
    {
        /**
         * @param  list<ClientInfoDTO>  $history
         */
        public function __construct(
            public readonly ClientInfoDTO $client,
            public readonly array $history,
            public readonly Role $role,
        ) {}
    };

    expect($dto->toArray())->toBe([
        'client' => ['ip_address' => '127.0.0.1', 'user_agent' => null],
        'history' => [['ip_address' => null, 'user_agent' => 'a']],
        'role' => 'author',
    ]);
});

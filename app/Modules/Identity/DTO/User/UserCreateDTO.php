<?php

declare(strict_types=1);

namespace App\Modules\Identity\DTO\User;

use App\Modules\Identity\DTO\BaseDTO;

class UserCreateDTO extends BaseDTO
{
    public function __construct(
        private string $name,
        private string $email,
        private string $password,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Identity\Enums;

enum Role: string
{
    case STUDENT = 'student';
    case AUTHOR = 'author';
    case ADMIN = 'admin';
}

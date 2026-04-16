<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: int
{
    case USER = 1;      // 0001
    case ADMIN = 2;     // 0010
    case PROVIDER = 4;  // 0100
}

<?php

declare(strict_types=1);

namespace App\Enums;

enum RoleName: string
{
    case Member = 'member';
    case Admin = 'admin';
    case Superadmin = 'superadmin';
}

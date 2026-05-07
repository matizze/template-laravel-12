<?php

namespace App\Enums;

enum RoleName: string
{
    case Member = 'member';
    case Admin = 'admin';
    case Superadmin = 'superadmin';
}

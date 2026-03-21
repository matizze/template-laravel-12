<?php

namespace App\Enums;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';
    case Guest = 'guest';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Dono',
            self::Admin => 'Admin',
            self::Member => 'Membro',
            self::Viewer => 'Visualizador',
            self::Guest => 'Convidado (Pendente)',
        };
    }

    public function isOwner(): bool
    {
        return $this === self::Owner;
    }

    public function canManageMembers(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }

    public function canViewAllContent(): bool
    {
        return $this !== self::Guest;
    }

    /** @return array<self> */
    public static function assignable(): array
    {
        return array_filter(self::cases(), fn (self $role) => $role !== self::Guest);
    }
}

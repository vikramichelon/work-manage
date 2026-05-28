<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case MEMBER = 'member';

    /** Human-friendly label. */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::MANAGER => 'Manager',
            self::MEMBER => 'Member',
        };
    }

    /** Bootstrap contextual colour used for role badges. */
    public function color(): string
    {
        return match ($this) {
            self::ADMIN => 'danger',
            self::MANAGER => 'primary',
            self::MEMBER => 'secondary',
        };
    }

    /** All roles as value => label, handy for <select> menus. */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $role) => $carry + [$role->value => $role->label()],
            []
        );
    }
}

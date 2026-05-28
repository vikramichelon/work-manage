<?php

namespace App\Enums;

enum Priority: string
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';

    public function label(): string
    {
        return match ($this) {
            self::HIGH => 'High',
            self::MEDIUM => 'Medium',
            self::LOW => 'Low',
        };
    }

    /** Bootstrap contextual colour for priority badges. */
    public function color(): string
    {
        return match ($this) {
            self::HIGH => 'danger',
            self::MEDIUM => 'warning',
            self::LOW => 'success',
        };
    }

    /** Lower number = higher priority (used for sorting High → Medium → Low). */
    public function weight(): int
    {
        return match ($this) {
            self::HIGH => 1,
            self::MEDIUM => 2,
            self::LOW => 3,
        };
    }

    /** Values in priority order — drives the SQL FIELD() sort. */
    public static function orderedValues(): array
    {
        return [self::HIGH->value, self::MEDIUM->value, self::LOW->value];
    }

    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $p) => $carry + [$p->value => $p->label()],
            []
        );
    }
}

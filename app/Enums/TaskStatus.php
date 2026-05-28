<?php

namespace App\Enums;

enum TaskStatus: string
{
    case TODO        = 'todo';
    case WIP         = 'wip';
    case FIRST_DRAFT = 'first_draft';
    case DONE        = 'done';
    case HOLD        = 'hold';

    public function label(): string
    {
        return match ($this) {
            self::TODO        => 'To Do',
            self::WIP         => 'WIP',
            self::FIRST_DRAFT => 'First Draft',
            self::DONE        => 'Done',
            self::HOLD        => 'Hold',
        };
    }

    /** Bootstrap contextual colour for status badges / Kanban columns. */
    public function color(): string
    {
        return match ($this) {
            self::TODO        => 'secondary',
            self::WIP         => 'info',
            self::FIRST_DRAFT => 'warning',
            self::DONE        => 'success',
            self::HOLD        => 'dark', // dark grey-purple for paused state
        };
    }

    /** Statuses where the deliverable URL is meaningful (review + done). */
    public function showsFinalUrl(): bool
    {
        return $this === self::FIRST_DRAFT || $this === self::DONE;
    }

    /** Tasks that are still "in play" (count toward queues, blink overdue, etc). */
    public function isActive(): bool
    {
        return $this !== self::DONE && $this !== self::HOLD;
    }

    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $s) => $carry + [$s->value => $s->label()],
            []
        );
    }
}

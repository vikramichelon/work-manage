<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'activity_template_id',
        'assigned_to',
        'assigned_by_name',
        'assigned_at',
        'title',
        'website',
        'description',
        'created_by',
        'priority',
        'status',
        'start_date',
        'due_date',
        'estimated_hours',
        'actual_hours',
        'wip_started_at',
        'delay_reason',
        'hold_reason',
        'final_url',
        'position',
        'completed_at',
    ];

    /** Working window: 10:00 — 18:00, Monday – Friday. */
    public const WORK_START_HOUR = 10;
    public const WORK_END_HOUR   = 18;
    public const WORK_MINUTES_PER_DAY = (self::WORK_END_HOUR - self::WORK_START_HOUR) * 60;

    protected function casts(): array
    {
        return [
            'priority' => Priority::class,
            'status' => TaskStatus::class,
            'assigned_at' => 'date',
            'start_date' => 'date',
            'due_date' => 'date',
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'wip_started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function activityTemplate(): BelongsTo
    {
        return $this->belongsTo(ActivityTemplate::class);
    }

    /** The user this task is assigned to (may be null). */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->orderBy('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class)->latest();
    }

    /**
     * Order rules (applied in this order):
     *  1. Open tasks first, Done at the very bottom.
     *  2. Done tasks: latest completed first (completed_at DESC) — priority/due_date ignored.
     *  3. Open tasks: priority High → Medium → Low, then nearest due date (NULL last).
     */
    public function scopeByPriority(Builder $query): Builder
    {
        $order = "'".implode("','", Priority::orderedValues())."'";
        $doneVal = TaskStatus::DONE->value;

        return $query
            // false (0) = open → first; true (1) = done → last
            ->orderByRaw("status = '{$doneVal}'")
            // Done tasks: latest completed first (the CASE returns NULL for open
            // tasks, so it has no effect on their ordering).
            ->orderByRaw("CASE WHEN status = '{$doneVal}' THEN UNIX_TIMESTAMP(completed_at) END DESC")
            // Open tasks: priority then due_date (Done tasks already sorted above).
            ->orderByRaw("FIELD(priority, {$order})")
            ->orderByRaw('due_date IS NULL, due_date');
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->status->isActive() // Done + Hold suppress the blink
            && $this->due_date->isPast()
            && ! $this->due_date->isToday();
    }

    /** Timeline date is today and the task is still open. */
    public function isDueToday(): bool
    {
        return $this->due_date
            && $this->status->isActive()
            && $this->due_date->isToday();
    }

    /**
     * Friendly relative date: "Aaj" / "Kal" / "3 din pehle" / "in 2 days"
     * — with a sensible fallback to "25 May" for far-off dates.
     */
    public static function smartDate(?\Carbon\Carbon $date): string
    {
        if (! $date) {
            return '—';
        }
        $today = \Carbon\Carbon::today();
        $diff = $today->diffInDays($date, false); // negative if past

        return match (true) {
            $diff === 0   => 'Aaj',
            $diff === 1   => 'Kal',
            $diff === -1  => 'Kal (past)',
            $diff > 1 && $diff <= 6   => "in {$diff} days",
            $diff < -1 && $diff >= -6 => abs($diff)." din pehle",
            default => $date->format('d M'),
        };
    }

    /** Render a decimal-hour value as "2h 15m" / "45m" / "1h". */
    public static function formatHours(int|float|null $hours): string
    {
        if ($hours === null) {
            return '—';
        }
        $totalMinutes = (int) round(((float) $hours) * 60);
        if ($totalMinutes === 0) {
            return '0m';
        }
        $h = intdiv($totalMinutes, 60);
        $m = $totalMinutes % 60;

        return match (true) {
            $h === 0 => "{$m}m",
            $m === 0 => "{$h}h",
            default  => "{$h}h {$m}m",
        };
    }

    public function estimatedDisplay(): string
    {
        return self::formatHours($this->estimated_hours);
    }

    public function actualDisplay(): string
    {
        return self::formatHours($this->actual_hours);
    }

    /**
     * Snap a moment forward into the next valid working slot
     * (Mon–Fri, 10:00–18:00). If `$when` is already inside the window,
     * returns it unchanged.
     */
    public static function nextWorkingMoment(\Carbon\Carbon $when): \Carbon\Carbon
    {
        $cursor = $when->copy();
        // Skip weekends
        while ($cursor->isWeekend()) {
            $cursor->addDay()->setTime(self::WORK_START_HOUR, 0);
        }
        // Before work start → snap to today's start
        if ($cursor->hour < self::WORK_START_HOUR) {
            $cursor->setTime(self::WORK_START_HOUR, 0);
        }
        // After work end → next working day's start
        if ($cursor->hour >= self::WORK_END_HOUR) {
            $cursor->addDay()->setTime(self::WORK_START_HOUR, 0);
            while ($cursor->isWeekend()) {
                $cursor->addDay();
            }
        }

        return $cursor;
    }

    /**
     * Count actual working minutes between two moments, clipped to the
     * 10am–6pm Mon–Fri window. Overnight, weekend, before-10am, after-6pm
     * intervals are all excluded.
     */
    public static function workingMinutesBetween(\Carbon\Carbon $start, \Carbon\Carbon $end): int
    {
        if ($end->lte($start)) {
            return 0;
        }

        $total = 0;
        $cursor = $start->copy();
        $stop = $end->copy();

        while ($cursor->lt($stop)) {
            if ($cursor->isWeekend()) {
                $cursor = $cursor->copy()->addDay()->setTime(self::WORK_START_HOUR, 0);
                continue;
            }

            $dayStart = $cursor->copy()->setTime(self::WORK_START_HOUR, 0);
            $dayEnd   = $cursor->copy()->setTime(self::WORK_END_HOUR, 0);

            // If past today's work end, jump to next day
            if ($cursor->gte($dayEnd)) {
                $cursor = $cursor->copy()->addDay()->setTime(self::WORK_START_HOUR, 0);
                continue;
            }

            $segStart = $cursor->lt($dayStart) ? $dayStart : $cursor;
            $segEnd   = $stop->lt($dayEnd) ? $stop : $dayEnd;

            if ($segEnd->gt($segStart)) {
                $total += $segStart->diffInMinutes($segEnd);
            }

            $cursor = $cursor->copy()->addDay()->setTime(self::WORK_START_HOUR, 0);
        }

        return $total;
    }

    /**
     * Add working minutes onto a starting moment, respecting the work
     * window. Returns the completion timestamp.
     */
    public static function addWorkingMinutes(\Carbon\Carbon $from, int $minutes): \Carbon\Carbon
    {
        $cursor = self::nextWorkingMoment($from);
        $remaining = max(0, $minutes);

        while ($remaining > 0) {
            while ($cursor->isWeekend()) {
                $cursor->addDay()->setTime(self::WORK_START_HOUR, 0);
            }
            $dayEnd = $cursor->copy()->setTime(self::WORK_END_HOUR, 0);
            $availableToday = max(0, $cursor->diffInMinutes($dayEnd));

            if ($remaining <= $availableToday) {
                $cursor->addMinutes($remaining);
                $remaining = 0;
            } else {
                $remaining -= $availableToday;
                $cursor = $cursor->copy()->addDay()->setTime(self::WORK_START_HOUR, 0);
            }
        }

        return $cursor;
    }
}

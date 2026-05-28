<?php

namespace App\Models;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    public $timestamps = false;
    protected $fillable = ['task_id', 'user_id', 'action', 'changes', 'created_at'];

    protected function casts(): array
    {
        return [
            'changes'    => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Human-readable summary, e.g. "vikram changed Status from To Do to WIP". */
    public function description(): string
    {
        $u = $this->user?->name ?? 'Someone';
        // Eloquent's Model has a protected $changes property — use the
        // explicit attribute getter to avoid the name collision.
        $changes = (array) ($this->getAttribute('changes') ?? []);

        return match ($this->action) {
            'created'         => "{$u} created this task",
            'deleted'         => "{$u} deleted \"".($changes['title'] ?? 'a task').'"',
            'updated'         => "{$u} ".$this->renderChanges($changes),
            'commented'       => "{$u} commented: \"".($changes['body'] ?? '').'"',
            'comment_deleted' => "{$u} deleted a comment",
            'attached'        => "{$u} attached \"".($changes['filename'] ?? 'a file').'"',
            'detached'        => "{$u} removed \"".($changes['filename'] ?? 'a file').'"',
            default           => "{$u} did something",
        };
    }

    /** Format updated-field diffs into a comma-separated phrase. */
    private function renderChanges(array $changes): string
    {
        $parts = [];
        foreach ($changes as $field => $diff) {
            $label = $this->fieldLabel($field);
            $old = $this->displayValue($field, $diff['old'] ?? null);
            $new = $this->displayValue($field, $diff['new'] ?? null);
            $parts[] = "changed {$label} from \"{$old}\" to \"{$new}\"";
        }

        return $parts ? implode(', ', $parts) : 'updated this task';
    }

    private function fieldLabel(string $field): string
    {
        return match ($field) {
            'title'            => 'Client/Website',
            'category_id'      => 'Category',
            'assigned_to'      => 'Assignee',
            'assigned_by_name' => 'Assign person',
            'priority'         => 'Priority',
            'status'           => 'Status',
            'assigned_at'      => 'Assign date',
            'due_date'         => 'Timeline',
            'estimated_hours'  => 'Est. time',
            'actual_hours'     => 'Actual time',
            'delay_reason'     => 'Notes',
            'description'      => 'Description',
            default            => ucwords(str_replace('_', ' ', $field)),
        };
    }

    private function displayValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return match ($field) {
            'priority' => Priority::tryFrom((string) $value)?->label() ?? (string) $value,
            'status'   => TaskStatus::tryFrom((string) $value)?->label() ?? (string) $value,
            'assigned_to' => optional(User::find($value))->name ?? "User #{$value}",
            'category_id' => optional(Category::find($value))->name ?? "Category #{$value}",
            default    => (string) $value,
        };
    }
}

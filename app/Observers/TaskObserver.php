<?php

namespace App\Observers;

use App\Models\Activity;
use App\Models\Task;
use Illuminate\Support\Facades\Storage;

class TaskObserver
{
    /** Fields we don't want noisy log entries for. */
    private const IGNORE = ['updated_at', 'completed_at', 'position'];

    public function created(Task $task): void
    {
        Activity::create([
            'task_id'    => $task->id,
            'user_id'    => auth()->id(),
            'action'     => 'created',
            'created_at' => now(),
        ]);
    }

    public function updated(Task $task): void
    {
        $changes = $task->getChanges();
        foreach (self::IGNORE as $f) {
            unset($changes[$f]);
        }
        if (empty($changes)) {
            return;
        }

        $diff = [];
        foreach ($changes as $field => $new) {
            $diff[$field] = [
                'old' => $task->getOriginal($field),
                'new' => $new,
            ];
        }

        Activity::create([
            'task_id'    => $task->id,
            'user_id'    => auth()->id(),
            'action'     => 'updated',
            'changes'    => $diff,
            'created_at' => now(),
        ]);
    }

    /** Wipe attachment files BEFORE the SQL cascade nukes the rows. */
    public function deleting(Task $task): void
    {
        foreach ($task->attachments as $attachment) {
            Storage::disk('local')->delete($attachment->stored_path);
        }
    }

    public function deleted(Task $task): void
    {
        Activity::create([
            'task_id'    => null, // task is gone; keep the audit row
            'user_id'    => auth()->id(),
            'action'     => 'deleted',
            'changes'    => ['title' => $task->title],
            'created_at' => now(),
        ]);
    }
}

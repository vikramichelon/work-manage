<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $fillable = [
        'task_id', 'user_id', 'original_name', 'stored_path', 'mime_type', 'size_bytes',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Is the file an image (so the UI can show a thumbnail)? */
    public function isImage(): bool
    {
        return $this->mime_type && str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Direct URL for inline use (e.g. <img src>). Returns the public-storage
     * URL when the file lives on the public disk; falls back to the auth-
     * protected download route for files still on the legacy local disk.
     */
    public function publicUrl(): string
    {
        if (Storage::disk('public')->exists($this->stored_path)) {
            return Storage::disk('public')->url($this->stored_path);
        }
        return route('attachments.download', $this);
    }

    /** Human-readable size, e.g. "245 KB" or "1.2 MB". */
    public function humanSize(): string
    {
        $bytes = (int) $this->size_bytes;
        if ($bytes < 1024) {
            return "{$bytes} B";
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024).' KB';
        }

        return round($bytes / (1024 * 1024), 1).' MB';
    }
}

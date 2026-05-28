<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityTemplate extends Model
{
    protected $fillable = ['name', 'estimated_minutes', 'category_id', 'created_by'];

    protected function casts(): array
    {
        return ['estimated_minutes' => 'integer'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Render minutes as "2h 15m" / "45m". */
    public function timeLabel(): string
    {
        return Task::formatHours($this->estimated_minutes / 60);
    }
}

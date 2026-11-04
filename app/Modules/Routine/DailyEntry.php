<?php

namespace App\Modules\Routine;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyEntry extends Model
{
    protected $fillable = ['habit_id', 'date', 'value', 'done_at', 'note'];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'done_at' => 'datetime',
            'value' => 'integer',
        ];
    }

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }
}

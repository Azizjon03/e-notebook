<?php

namespace App\Modules\Study;

use App\Modules\Core\Area;
use App\Modules\Core\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudySession extends Model
{
    use BelongsToUser;

    protected $fillable = ['user_id', 'area_id', 'title', 'planned_minutes', 'started_at', 'ended_at', 'minutes'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function isRunning(): bool
    {
        return $this->ended_at === null;
    }

    public static function pointsFor(int $minutes): int
    {
        if ($minutes >= (int) config('points.study_full_minutes')) {
            return (int) config('points.study_full');
        }

        return intdiv($minutes, 5) * (int) config('points.study_per_5_minutes');
    }
}

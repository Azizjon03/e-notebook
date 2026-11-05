<?php

namespace App\Modules\Study;

use App\Modules\Core\Area;
use App\Modules\Core\Concerns\BelongsToUser;
use App\Modules\Ilm\Book;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudySession extends Model
{
    use BelongsToUser;

    protected $fillable = ['user_id', 'area_id', 'title', 'planned_minutes', 'started_at', 'ended_at', 'minutes', 'book_id', 'start_page', 'end_page', 'pages_read'];

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

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
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

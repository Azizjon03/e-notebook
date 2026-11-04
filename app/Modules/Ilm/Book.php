<?php

namespace App\Modules\Ilm;

use App\Modules\Core\Area;
use App\Modules\Core\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use BelongsToUser;

    public const STATUSES = ['planned', 'reading', 'finished'];

    protected $fillable = ['user_id', 'area_id', 'title', 'author', 'stage', 'position', 'total_pages', 'current_page', 'status', 'started_on', 'finished_on'];

    protected function casts(): array
    {
        return [
            'started_on' => 'date:Y-m-d',
            'finished_on' => 'date:Y-m-d',
            'total_pages' => 'integer',
            'current_page' => 'integer',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ReadingLog::class);
    }

    public function percent(): int
    {
        return $this->total_pages ? (int) min(100, round($this->current_page / $this->total_pages * 100)) : 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toCard(): array
    {
        $last = $this->relationLoaded('logs') ? $this->logs->sortByDesc('id')->first() : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'stage' => $this->stage,
            'area' => $this->area?->key,
            'status' => $this->status,
            'total_pages' => $this->total_pages,
            'current_page' => $this->current_page,
            'percent' => $this->percent(),
            'last_chapter' => $last?->chapter,
            'last_read' => $last?->date?->toDateString(),
            'finished_on' => $this->finished_on?->toDateString(),
        ];
    }
}

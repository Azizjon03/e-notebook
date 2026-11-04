<?php

namespace App\Modules\Planner;

use App\Modules\Core\Concerns\BelongsToUser;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use BelongsToUser;

    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'date', 'start_time', 'end_time', 'done_at', 'moved_from_date', 'position'];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'moved_from_date' => 'date:Y-m-d',
            'done_at' => 'datetime',
        ];
    }

    protected static function newFactory(): TaskFactory
    {
        return TaskFactory::new();
    }

    public function isDone(): bool
    {
        return $this->done_at !== null;
    }

    public function scopeOnDate(Builder $query, string $date): void
    {
        $query->whereDate('date', $date);
    }

    public function scopeOrdered(Builder $query): void
    {
        // Vaqti bor vazifalar vaqt bo'yicha yuqorida, qolganlari qo'shilish tartibida.
        $query->orderByRaw('start_time is null')->orderBy('start_time')->orderBy('position')->orderBy('id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toCard(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'date' => $this->date->toDateString(),
            'start_time' => $this->start_time ? substr($this->start_time, 0, 5) : null,
            'end_time' => $this->end_time ? substr($this->end_time, 0, 5) : null,
            'done' => $this->isDone(),
            'moved_from' => $this->moved_from_date?->toDateString(),
        ];
    }
}

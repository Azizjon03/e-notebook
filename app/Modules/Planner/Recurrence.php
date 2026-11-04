<?php

namespace App\Modules\Planner;

use App\Modules\Core\Concerns\BelongsToUser;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Takrorlanadigan vazifa qoidasi. Nusxalar (tasks) kerak bo'lganda, ya'ni
 * foydalanuvchi o'sha kunni ko'rganda yaratiladi.
 */
class Recurrence extends Model
{
    use BelongsToUser;

    public const FREQUENCIES = ['daily', 'weekly', 'monthly'];

    protected $fillable = ['user_id', 'title', 'start_time', 'end_time', 'frequency', 'weekdays', 'starts_on', 'until', 'skipped_dates'];

    protected function casts(): array
    {
        return [
            'weekdays' => 'array',
            'skipped_dates' => 'array',
            'starts_on' => 'immutable_date',
            'until' => 'immutable_date',
        ];
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function occursOn(CarbonImmutable $day): bool
    {
        if ($day->lt($this->starts_on) || ($this->until && $day->gt($this->until))) {
            return false;
        }

        if (in_array($day->toDateString(), $this->skipped_dates ?? [], true)) {
            return false;
        }

        return match ($this->frequency) {
            'daily' => true,
            'weekly' => in_array($day->dayOfWeekIso, $this->weekdays ?? [$this->starts_on->dayOfWeekIso], true),
            // Oyning 31-kuni kabi kunlar qisqa oylarda oxirgi kunga tushadi.
            'monthly' => $day->day === min($this->starts_on->day, $day->daysInMonth),
            default => false,
        };
    }

    public function skip(string $date): void
    {
        $this->update(['skipped_dates' => array_values(array_unique([...($this->skipped_dates ?? []), $date]))]);
    }

    public function describe(): string
    {
        $days = ['', 'Du', 'Se', 'Cho', 'Pa', 'Ju', 'Sha', 'Ya'];

        return match ($this->frequency) {
            'daily' => 'Har kuni',
            'weekly' => 'Har hafta: '.collect($this->weekdays ?? [])->sort()->map(fn ($d) => $days[$d])->implode(', '),
            'monthly' => 'Har oy '.$this->starts_on->day.'-kuni',
            default => '',
        };
    }
}

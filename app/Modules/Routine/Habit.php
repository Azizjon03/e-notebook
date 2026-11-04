<?php

namespace App\Modules\Routine;

use App\Modules\Core\Area;
use App\Modules\Core\Concerns\BelongsToUser;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Habit extends Model
{
    use BelongsToUser;

    public const PRAYER_KEYS = ['fajr', 'dhuhr', 'asr', 'maghrib', 'isha'];

    protected $fillable = ['user_id', 'area_id', 'key', 'name', 'kind', 'target', 'weekdays', 'is_active', 'sort'];

    protected function casts(): array
    {
        return [
            'weekdays' => 'array',
            'is_active' => 'boolean',
            'target' => 'integer',
        ];
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(DailyEntry::class);
    }

    public function isPrayer(): bool
    {
        return in_array($this->key, self::PRAYER_KEYS, true);
    }

    public function isScheduledOn(CarbonImmutable $day): bool
    {
        return $this->weekdays === null || in_array($day->dayOfWeekIso, $this->weekdays, true);
    }

    /**
     * Qiymat kiritilganda bajarilganmi: check — belgilangan, count/duration — maqsadga yetgan.
     */
    public function isReachedBy(?int $value): bool
    {
        return match ($this->kind) {
            'check' => true,
            default => $value !== null && $value >= (int) $this->target,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toCard(?DailyEntry $entry): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'kind' => $this->kind,
            'target' => $this->target,
            'area' => $this->area?->key,
            'value' => $entry?->value,
            'done' => $entry?->done_at !== null,
        ];
    }
}

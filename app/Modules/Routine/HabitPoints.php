<?php

namespace App\Modules\Routine;

use App\Modules\Core\Points;
use Carbon\CarbonImmutable;

/**
 * Odat belgilanganda ball beradi, belgi olinganda qaytarib oladi.
 */
class HabitPoints
{
    public function __construct(private readonly Points $points) {}

    public function sync(Habit $habit, DailyEntry $entry): void
    {
        $user = $habit->user;

        if (! $entry->done_at) {
            $this->points->revoke($entry, 'done');
            $this->points->revoke($entry, 'streak');

            return;
        }

        $this->points->award($user, $entry, 'done', $this->pointsFor($habit));

        $days = (int) config('points.streak_days');
        $streak = $this->streak($habit, CarbonImmutable::parse($entry->date->toDateString()));
        if ($streak > 0 && $streak % $days === 0) {
            $this->points->award($user, $entry, 'streak', (int) config('points.streak_bonus'));
        }
    }

    private function pointsFor(Habit $habit): int
    {
        return match (true) {
            $habit->isPrayer() => (int) config('points.prayer'),
            $habit->key === 'gym' => (int) config('points.gym'),
            $habit->key === 'steps' => (int) config('points.steps'),
            default => (int) config('points.custom_habit'),
        };
    }

    /**
     * Shu kun bilan tugaydigan, odat rejalashtirilgan kunlar bo'yicha ketma-ket bajarilgan kunlar soni.
     */
    public function streak(Habit $habit, CarbonImmutable $day): int
    {
        $done = DailyEntry::where('habit_id', $habit->id)
            ->whereNotNull('done_at')
            ->whereDate('date', '<=', $day->toDateString())
            ->whereDate('date', '>=', $day->subDays(370)->toDateString())
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->flip();

        $count = 0;
        for ($d = $day; $d->gte($day->subDays(370)); $d = $d->subDay()) {
            if (! $habit->isScheduledOn($d)) {
                continue;
            }
            if (! $done->has($d->toDateString())) {
                break;
            }
            $count++;
        }

        return $count;
    }
}

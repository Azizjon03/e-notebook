<?php

namespace App\Modules\Planner;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;

/**
 * Berilgan oraliq uchun takrorlanadigan vazifalarning nusxalarini yaratadi.
 * Qayta chaqirilsa ham ikki marta yaratmaydi (unique(recurrence_id, date)).
 */
class RecurrenceExpander
{
    /** Uzoq kelajak uchun nusxa yaratilmaydi. */
    public const MAX_DAYS_AHEAD = 62;

    public function expand(CarbonImmutable $from, CarbonImmutable $to, CarbonImmutable $today): void
    {
        $to = $to->min($today->addDays(self::MAX_DAYS_AHEAD));
        // O'tmish uchun nusxa yaratilmaydi: bajarilmagan "eski" vazifalar to'planib qolmasin.
        $from = $from->max($today);
        if ($from->gt($to)) {
            return;
        }

        $rules = Recurrence::where('starts_on', '<=', $to->toDateString())
            ->where(fn ($q) => $q->whereNull('until')->orWhere('until', '>=', $from->toDateString()))
            ->get();

        foreach ($rules as $rule) {
            $existing = $rule->tasks()
                ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                ->pluck('date')
                ->map(fn ($d) => $d->toDateString())
                ->all();

            foreach (CarbonPeriodImmutable::create($from, $to) as $day) {
                if (! in_array($day->toDateString(), $existing, true) && $rule->occursOn($day)) {
                    Task::create([
                        'user_id' => $rule->user_id,
                        'recurrence_id' => $rule->id,
                        'title' => $rule->title,
                        'date' => $day->toDateString(),
                        'start_time' => $rule->start_time,
                        'end_time' => $rule->end_time,
                    ]);
                }
            }
        }
    }
}

<?php

namespace App\Modules\Core\Reminders;

use App\Models\User;
use App\Modules\Planner\Task;
use App\Modules\Routine\Habit;
use App\Modules\Routine\PrayerTime;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Foydalanuvchi uchun hozir yuborilishi kerak bo'lgan eslatmalarni topadi.
 * Eslatma vaqti kelgandan keyin WINDOW daqiqa ichida yuboriladi (scheduler kechiksa ham yo'qolmaydi),
 * reminder_logs esa takrorlanishni oldini oladi.
 */
class ReminderPlanner
{
    public const WINDOW = 5;

    public const RETRY_AFTER = 30;

    /**
     * @return list<Reminder>
     */
    public function due(User $user, CarbonImmutable $nowUtc): array
    {
        $now = $nowUtc->setTimezone($user->timezone);
        $today = $now->toDateString();
        $sent = DB::table('reminder_logs')->where('user_id', $user->id)
            ->where('sent_at', '>=', $nowUtc->subDays(2))
            ->pluck('sent_at', 'ref');

        $due = [
            ...$this->prayers($user, $now),
            ...$this->tasks($user, $now),
            ...$this->planning($user, $now, $sent->all()),
            ...$this->weekly($user, $now),
        ];

        return array_values(array_filter($due, fn (Reminder $r) => ! $sent->has($r->ref)));
    }

    private function isDue(CarbonImmutable $now, CarbonImmutable $at): bool
    {
        return $now->gte($at) && $now->lt($at->addMinutes(self::WINDOW));
    }

    /**
     * @return list<Reminder>
     */
    private function prayers(User $user, CarbonImmutable $now): array
    {
        if (! $user->prayer_reminders || ! $user->region_id) {
            return [];
        }

        $row = PrayerTime::where('region_id', $user->region_id)->whereDate('date', $now->toDateString())->first();
        if (! $row) {
            return [];
        }

        // Faqat yoqilgan namoz odatlari uchun.
        $active = Habit::withoutGlobalScope('owner')->where('user_id', $user->id)
            ->whereIn('key', Habit::PRAYER_KEYS)->where('is_active', true)->pluck('key')->all();

        $out = [];
        foreach ($active as $key) {
            $time = CarbonImmutable::parse($now->toDateString().' '.$row->{$key}, $user->timezone);
            if ($this->isDue($now, $time->subMinutes($user->prayer_remind_before))) {
                $out[] = new Reminder(
                    "prayer:{$key}:{$now->toDateString()}",
                    PrayerTime::NAMES[$key].' vaqti '.$row->{$key}.' da kiradi.',
                );
            }
        }

        return $out;
    }

    /**
     * @return list<Reminder>
     */
    private function tasks(User $user, CarbonImmutable $now): array
    {
        if (! $user->task_reminders) {
            return [];
        }

        return Task::withoutGlobalScope('owner')->where('user_id', $user->id)
            ->whereDate('date', $now->toDateString())->whereNull('done_at')->whereNotNull('start_time')
            ->get()
            ->filter(function (Task $t) use ($user, $now) {
                $start = CarbonImmutable::parse($now->toDateString().' '.substr($t->start_time, 0, 5), $user->timezone);

                return $this->isDue($now, $start->subMinutes($user->task_remind_before));
            })
            ->map(fn (Task $t) => new Reminder(
                "task:{$t->id}",
                '<b>'.e($t->title).'</b> '.substr($t->start_time, 0, 5).' da boshlanadi.',
                $this->button('Bugungi reja', '/'),
            ))
            ->values()
            ->all();
    }

    /**
     * Kunlik rejalashtirish eslatmasi va 30 daqiqadan keyin bitta qayta eslatma.
     *
     * @param  array<string, string>  $sent
     * @return list<Reminder>
     */
    private function planning(User $user, CarbonImmutable $now, array $sent): array
    {
        if ($user->planning_mode === 'off') {
            return [];
        }

        $at = CarbonImmutable::parse($now->toDateString().' '.$user->planning_time, $user->timezone);
        $target = $user->planning_mode === 'evening' ? $now->addDay() : $now;
        $targetDate = $target->toDateString();
        $label = $user->planning_mode === 'evening' ? 'Ertangi kunni' : 'Bugungi kunni';

        $planned = Task::withoutGlobalScope('owner')->where('user_id', $user->id)->whereDate('date', $targetDate)->count();
        $leftToday = Task::withoutGlobalScope('owner')->where('user_id', $user->id)
            ->whereDate('date', $now->toDateString())->whereNull('done_at')->count();

        $out = [];
        if ($this->isDue($now, $at)) {
            $text = "{$label} rejalashtirish vaqti bo'ldi.";
            if ($user->planning_mode === 'evening' && $leftToday > 0) {
                $text .= " Bugundan {$leftToday} ta bajarilmagan vazifa qoldi, ularni ertaga o'tkazishingiz mumkin.";
            }
            $out[] = new Reminder("plan:{$targetDate}", $text, $this->button('Rejalashtirish', '/?date='.$targetDate));
        }

        // Qayta eslatma: birinchisi yuborilgan, 30 daqiqa o'tgan va hali reja yo'q bo'lsa.
        if (isset($sent["plan:{$targetDate}"]) && $planned === 0 && $this->isDue($now, $at->addMinutes(self::RETRY_AFTER))) {
            $out[] = new Reminder(
                "plan-again:{$targetDate}",
                "{$label} hali rejalashtirmadingiz. 5 daqiqa ajrating.",
                $this->button('Rejalashtirish', '/?date='.$targetDate),
            );
        }

        return $out;
    }

    /**
     * @return list<Reminder>
     */
    private function weekly(User $user, CarbonImmutable $now): array
    {
        if (! $user->weekly_planning_day || $now->dayOfWeekIso !== $user->weekly_planning_day) {
            return [];
        }

        $at = CarbonImmutable::parse($now->toDateString().' '.$user->weekly_planning_time, $user->timezone);
        if (! $this->isDue($now, $at)) {
            return [];
        }

        $nextMonday = $now->next(CarbonImmutable::MONDAY)->toDateString();

        return [new Reminder(
            'week:'.$nextMonday,
            'Haftalik reja vaqti: kelasi hafta uchun asosiy maqsadlar va vazifalarni belgilang.',
            $this->button('Haftani rejalashtirish', '/calendar?view=week&date='.$nextMonday),
        )];
    }

    /**
     * @return array<string, mixed>
     */
    private function button(string $text, string $path): array
    {
        return ['inline_keyboard' => [[
            ['text' => $text, 'web_app' => ['url' => rtrim((string) config('app.url'), '/').$path]],
        ]]];
    }
}

<?php

namespace App\Modules\Core\Reminders;

use App\Models\User;
use App\Modules\Core\I18n\Locale;
use App\Modules\Planner\Task;
use App\Modules\Routine\Habit;
use App\Modules\Routine\PrayerTime;
use App\Modules\Study\StudySession;
use App\Modules\Telegram\Summaries;
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

    /** Kun yakuni xabari vaqti (foydalanuvchi vaqti bilan). */
    public const DAY_SUMMARY_AT = '21:30';

    public function __construct(private readonly Summaries $summaries) {}

    /**
     * @return list<Reminder>
     */
    public function due(User $user, CarbonImmutable $nowUtc): array
    {
        // Eslatmalar foydalanuvchi tilida tuziladi.
        $previous = app()->getLocale();
        app()->setLocale(Locale::valid($user->locale));

        try {
            return $this->collect($user, $nowUtc);
        } finally {
            app()->setLocale($previous);
        }
    }

    /**
     * @return list<Reminder>
     */
    private function collect(User $user, CarbonImmutable $nowUtc): array
    {
        $now = $nowUtc->setTimezone($user->timezone);
        $sent = DB::table('reminder_logs')->where('user_id', $user->id)
            ->where('sent_at', '>=', $nowUtc->subDays(2))
            ->pluck('sent_at', 'ref');

        $due = [
            ...$this->prayers($user, $now),
            ...$this->tasks($user, $now),
            ...$this->planning($user, $now, $sent->all()),
            ...$this->weekly($user, $now),
            ...$this->daySummary($user, $now),
            ...$this->studyTimers($user, $now),
        ];

        return array_values(array_filter($due, fn (Reminder $r) => ! $sent->has($r->ref)));
    }

    private function isDue(CarbonImmutable $now, CarbonImmutable $at): bool
    {
        return $now->gte($at) && $now->lt($at->addMinutes(self::WINDOW));
    }

    /**
     * Ochiq o'qish sessiyasining rejalashtirilgan vaqti tugaganda (ilova yopiq bo'lsa ham) bildiradi.
     *
     * @return list<Reminder>
     */
    private function studyTimers(User $user, CarbonImmutable $now): array
    {
        return StudySession::withoutGlobalScope('owner')->where('user_id', $user->id)
            ->whereNull('ended_at')->where('started_at', '>=', $now->subDay()->utc())
            ->get()
            ->filter(fn (StudySession $s) => $this->isDue($now, CarbonImmutable::parse($s->started_at)->addMinutes($s->planned_minutes)))
            ->map(fn (StudySession $s) => new Reminder(
                "study:{$s->id}",
                __("⏰ :minutes daqiqalik o'qish vaqti tugadi: :title. Sessiyani ilovada tugating.", [
                    'minutes' => $s->planned_minutes,
                    'title' => $s->title ?: __("O'qish sessiyasi"),
                ]),
            ))
            ->values()->all();
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
                    __(':prayer vaqti :time da kiradi.', ['prayer' => __(PrayerTime::NAMES[$key]), 'time' => $row->{$key}]),
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
                __(':task :time da boshlanadi.', ['task' => '<b>'.e($t->title).'</b>', 'time' => substr($t->start_time, 0, 5)]),
                $this->button(__('Bugungi reja'), '/'),
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

        $planned = Task::withoutGlobalScope('owner')->where('user_id', $user->id)->whereDate('date', $targetDate)->count();
        $leftToday = Task::withoutGlobalScope('owner')->where('user_id', $user->id)
            ->whereDate('date', $now->toDateString())->whereNull('done_at')->count();

        $out = [];
        if ($this->isDue($now, $at)) {
            $text = $user->planning_mode === 'evening'
                ? __("Ertangi kunni rejalashtirish vaqti bo'ldi.")
                : __("Bugungi kunni rejalashtirish vaqti bo'ldi.");
            if ($user->planning_mode === 'evening' && $leftToday > 0) {
                $text .= ' '.__("Bugundan :count ta bajarilmagan vazifa qoldi, ularni ertaga o'tkazishingiz mumkin.", ['count' => $leftToday]);
            }
            $out[] = new Reminder("plan:{$targetDate}", $text, $this->button(__('Rejalashtirish'), '/?date='.$targetDate));
        }

        // Qayta eslatma: birinchisi yuborilgan, 30 daqiqa o'tgan va hali reja yo'q bo'lsa.
        if (isset($sent["plan:{$targetDate}"]) && $planned === 0 && $this->isDue($now, $at->addMinutes(self::RETRY_AFTER))) {
            $out[] = new Reminder(
                "plan-again:{$targetDate}",
                $user->planning_mode === 'evening'
                    ? __('Ertangi kunni hali rejalashtirmadingiz. 5 daqiqa ajrating.')
                    : __('Bugungi kunni hali rejalashtirmadingiz. 5 daqiqa ajrating.'),
                $this->button(__('Rejalashtirish'), '/?date='.$targetDate),
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
            __('Haftalik reja vaqti: kelasi hafta uchun asosiy maqsadlar va vazifalarni belgilang.'),
            $this->button(__('Haftani rejalashtirish'), '/calendar?view=week&date='.$nextMonday),
        )];
    }

    /**
     * @return list<Reminder>
     */
    private function daySummary(User $user, CarbonImmutable $now): array
    {
        if (! $user->day_summary) {
            return [];
        }

        $at = CarbonImmutable::parse($now->toDateString().' '.self::DAY_SUMMARY_AT, $user->timezone);
        if (! $this->isDue($now, $at)) {
            return [];
        }

        $text = $this->summaries->daySummary($user, $now);

        return $text ? [new Reminder('summary:'.$now->toDateString(), $text, $this->button(__('Natijalar'), '/study'))] : [];
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

<?php

namespace App\Modules\Telegram;

use App\Models\User;
use App\Modules\Ilm\Book;
use App\Modules\Ilm\ReadingLog;
use App\Modules\Planner\Task;
use App\Modules\Routine\DailyEntry;
use App\Modules\Routine\Habit;
use App\Modules\Routine\PrayerTime;
use App\Modules\Study\StudySession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Bot uchun kunlik va haftalik xulosa matnlari. Hamma so'rovlar aniq user_id bilan
 * (webhook va scheduler'da Auth yo'q).
 */
class Summaries
{
    public function today(User $user, CarbonImmutable $day): string
    {
        $date = $day->toDateString();
        $tasks = Task::withoutGlobalScope('owner')->where('user_id', $user->id)->whereDate('date', $date)
            ->orderByRaw('start_time is null')->orderBy('start_time')->get();

        $lines = ['<b>'.$this->dayTitle($day).'</b>', ''];

        if ($tasks->isEmpty()) {
            $lines[] = "Bu kunga vazifa yo'q.";
        } else {
            $lines[] = 'Vazifalar: '.$tasks->whereNotNull('done_at')->count().'/'.$tasks->count();
            foreach ($tasks->take(10) as $t) {
                $time = $t->start_time ? substr($t->start_time, 0, 5).' ' : '';
                $lines[] = ($t->done_at ? '✅ ' : '▫️ ').$time.e($t->title);
            }
            if ($tasks->count() > 10) {
                $lines[] = '… yana '.($tasks->count() - 10).' ta';
            }
        }

        $prayer = $user->region_id ? PrayerTime::where('region_id', $user->region_id)->whereDate('date', $date)->first() : null;
        if ($prayer) {
            $lines[] = '';
            $lines[] = 'Namoz: '.collect(['fajr', 'dhuhr', 'asr', 'maghrib', 'isha'])
                ->map(fn ($k) => PrayerTime::NAMES[$k].' '.$prayer->{$k})->implode(' · ');
        }

        return implode("\n", $lines);
    }

    public function daySummary(User $user, CarbonImmutable $day): ?string
    {
        $date = $day->toDateString();
        $tasks = Task::withoutGlobalScope('owner')->where('user_id', $user->id)->whereDate('date', $date)->get();
        $habits = Habit::withoutGlobalScope('owner')->where('user_id', $user->id)->where('is_active', true)->get()
            ->filter(fn (Habit $h) => $h->isScheduledOn($day));
        $entries = DailyEntry::whereIn('habit_id', $habits->pluck('id'))->whereDate('date', $date)->get()->keyBy('habit_id');
        $minutes = $this->studyMinutes($user, $day->startOfDay(), $day->endOfDay());
        $points = $this->points($user, $day->startOfDay(), $day->endOfDay());

        if ($tasks->isEmpty() && $entries->isEmpty() && $minutes === 0) {
            return null; // Hech narsa qilinmagan kunda xulosa yuborilmaydi.
        }

        $lines = ['<b>Kun yakuni</b>', ''];
        if ($tasks->isNotEmpty()) {
            $done = $tasks->whereNotNull('done_at')->count();
            $lines[] = "Vazifalar: {$done}/{$tasks->count()}";
        }

        $prayers = $habits->filter->isPrayer();
        if ($prayers->isNotEmpty()) {
            $lines[] = 'Namoz: '.$prayers->filter(fn ($h) => $entries->get($h->id)?->done_at)->count().'/'.$prayers->count();
        }
        foreach ($habits->reject->isPrayer() as $h) {
            $entry = $entries->get($h->id);
            $lines[] = $h->name.': '.match ($h->kind) {
                'check' => $entry?->done_at ? 'bajarildi' : 'bajarilmadi',
                default => number_format((int) $entry?->value, 0, '.', ' ').' / '.number_format((int) $h->target, 0, '.', ' '),
            };
        }
        if ($minutes > 0) {
            $lines[] = "O'qish: {$minutes} daqiqa";
        }
        $lines[] = '';
        $lines[] = "Bugun: +{$points} ball";

        $left = $tasks->whereNull('done_at')->count();
        if ($left > 0) {
            $lines[] = "{$left} ta vazifa qoldi, ularni ertaga o'tkazishingiz mumkin.";
        }

        return implode("\n", $lines);
    }

    public function week(User $user, CarbonImmutable $today): string
    {
        $from = $today->subDays(6)->startOfDay();
        $to = $today->endOfDay();

        $tasks = Task::withoutGlobalScope('owner')->where('user_id', $user->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])->get();

        $prayerIds = Habit::withoutGlobalScope('owner')->where('user_id', $user->id)
            ->whereIn('key', Habit::PRAYER_KEYS)->where('is_active', true)->pluck('id');
        $prayed = DailyEntry::whereIn('habit_id', $prayerIds)->whereNotNull('done_at')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])->count();

        $bookIds = Book::withoutGlobalScope('owner')->where('user_id', $user->id)->pluck('id');
        $pages = ReadingLog::whereIn('book_id', $bookIds)->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()->sum(fn (ReadingLog $l) => $l->pages());

        $lines = [
            '<b>Oxirgi 7 kun</b> ('.$from->format('d.m').' – '.$to->format('d.m').')',
            '',
            'Vazifalar: '.$tasks->whereNotNull('done_at')->count().'/'.$tasks->count(),
        ];
        if ($prayerIds->isNotEmpty()) {
            $lines[] = "Namoz: {$prayed}/".($prayerIds->count() * 7);
        }
        $lines[] = "O'qish: ".$this->studyMinutes($user, $from, $to).' daqiqa';
        if ($pages > 0) {
            $lines[] = "Kitob: {$pages} bet";
        }
        $lines[] = 'Ball: +'.$this->points($user, $from, $to);

        return implode("\n", $lines);
    }

    private function studyMinutes(User $user, CarbonImmutable $from, CarbonImmutable $to): int
    {
        return (int) StudySession::withoutGlobalScope('owner')->where('user_id', $user->id)
            ->whereBetween('started_at', [$from->utc(), $to->utc()])->sum('minutes');
    }

    private function points(User $user, CarbonImmutable $from, CarbonImmutable $to): int
    {
        return (int) DB::table('points_ledger')->where('user_id', $user->id)
            ->whereBetween('created_at', [$from->utc(), $to->utc()])->sum('points');
    }

    private function dayTitle(CarbonImmutable $day): string
    {
        $days = [1 => 'Dushanba', 'Seshanba', 'Chorshanba', 'Payshanba', 'Juma', 'Shanba', 'Yakshanba'];
        $months = [1 => 'yanvar', 'fevral', 'mart', 'aprel', 'may', 'iyun', 'iyul', 'avgust', 'sentabr', 'oktabr', 'noyabr', 'dekabr'];

        return $days[$day->dayOfWeekIso].', '.$day->day.'-'.$months[$day->month];
    }
}

<?php

namespace App\Modules\Core;

use App\Modules\Ilm\ReadingLog;
use App\Modules\Planner\Task;
use App\Modules\Routine\DailyEntry;
use App\Modules\Routine\Habit;
use App\Modules\Study\StudySession;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StatsController
{
    public function __invoke(Request $request, Points $points): Response
    {
        $user = $request->user();
        $tz = $user->timezone;
        $range = $request->query('range') === '30' ? 30 : 7;
        $to = CarbonImmutable::now($tz)->startOfDay();
        $from = $to->subDays($range - 1);
        $fromDate = $from->toDateString();
        $toDate = $to->toDateString();

        $tasks = Task::whereBetween('date', [$fromDate, $toDate])->get(['date', 'done_at'])
            ->groupBy(fn ($t) => $t->date->toDateString());

        $habits = Habit::where('is_active', true)->orderBy('sort')->get();
        $entries = DailyEntry::whereIn('habit_id', $habits->pluck('id'))
            ->whereBetween('date', [$fromDate, $toDate])->whereNotNull('done_at')->get(['habit_id', 'date']);
        $prayerIds = $habits->filter->isPrayer()->pluck('id');

        $study = StudySession::whereBetween('started_at', [$from->utc(), $to->endOfDay()->utc()])->whereNotNull('minutes')
            ->get(['started_at', 'minutes'])->groupBy(fn ($s) => $s->started_at->timezone($tz)->toDateString());

        $ledger = DB::table('points_ledger')->where('user_id', $user->id)
            ->whereBetween('created_at', [$from->utc(), $to->endOfDay()->utc()])->get(['points', 'created_at'])
            ->groupBy(fn ($r) => CarbonImmutable::parse($r->created_at, 'UTC')->timezone($tz)->toDateString());

        $pages = ReadingLog::whereHas('book', fn ($q) => $q->where('user_id', $user->id))
            ->whereBetween('date', [$fromDate, $toDate])->get()->sum(fn (ReadingLog $l) => $l->pages());

        $days = collect(CarbonPeriodImmutable::create($from, $to))->map(function (CarbonImmutable $d) use ($tasks, $entries, $prayerIds, $study, $ledger) {
            $date = $d->toDateString();
            $dayTasks = $tasks->get($date, collect());

            return [
                'date' => $date,
                'tasks_done' => $dayTasks->whereNotNull('done_at')->count(),
                'tasks_total' => $dayTasks->count(),
                'prayers' => $entries->filter(fn ($e) => $e->date->toDateString() === $date && $prayerIds->contains($e->habit_id))->count(),
                'study' => (int) $study->get($date, collect())->sum('minutes'),
                'points' => (int) $ledger->get($date, collect())->sum('points'),
            ];
        })->values();

        $habitRates = $habits->reject->isPrayer()->map(function (Habit $h) use ($entries, $from, $to) {
            $scheduled = collect(CarbonPeriodImmutable::create($from, $to))->filter(fn ($d) => $h->isScheduledOn($d))->count();
            $done = $entries->where('habit_id', $h->id)->count();

            return ['name' => $h->displayName(), 'done' => $done, 'scheduled' => $scheduled];
        })->values();

        $totalTasks = $days->sum('tasks_total');

        return Inertia::render('Stats', [
            'range' => $range,
            'days' => $days,
            'summary' => [
                'tasks_rate' => $totalTasks ? (int) round($days->sum('tasks_done') / $totalTasks * 100) : null,
                'tasks_done' => $days->sum('tasks_done'),
                'prayers' => $days->sum('prayers'),
                'prayers_possible' => $prayerIds->count() * $range,
                'study_minutes' => $days->sum('study'),
                'pages' => $pages,
                'points' => $days->sum('points'),
            ],
            'habits' => $habitRates,
            'level' => Points::level($points->total($user)),
        ]);
    }
}

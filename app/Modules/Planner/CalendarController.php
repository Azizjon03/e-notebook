<?php

namespace App\Modules\Planner;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriodImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController
{
    public function __construct(private readonly RecurrenceExpander $expander) {}

    public function __invoke(Request $request): Response
    {
        $today = CarbonImmutable::now($request->user()->timezone)->startOfDay();
        $view = $request->query('view') === 'week' ? 'week' : 'month';
        $anchor = $this->parseDate($request->query('date')) ?? $today;

        if ($view === 'week') {
            $from = $anchor->startOfWeek(CarbonImmutable::MONDAY);
            $to = $from->addDays(6);
        } else {
            $from = $anchor->startOfMonth()->startOfWeek(CarbonImmutable::MONDAY);
            $to = $anchor->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY)->startOfDay();
        }

        $this->expander->expand($from, $to, $today);

        $tasks = Task::whereBetween('date', [$from->toDateString(), $to->toDateString()])->ordered()->get()
            ->groupBy(fn (Task $t) => $t->date->toDateString());

        $days = collect(CarbonPeriodImmutable::create($from, $to))->map(function (CarbonImmutable $day) use ($tasks, $view) {
            $list = $tasks->get($day->toDateString(), collect());

            return [
                'date' => $day->toDateString(),
                'total' => $list->count(),
                'done' => $list->whereNotNull('done_at')->count(),
                'tasks' => $view === 'week'
                    ? $list->map->toCard()->values()
                    : $list->take(3)->map(fn (Task $t) => ['title' => $t->title, 'done' => $t->isDone()])->values(),
            ];
        })->values();

        return Inertia::render('Calendar', [
            'view' => $view,
            'anchor' => $anchor->toDateString(),
            'month' => $anchor->format('Y-m'),
            'today' => $today->toDateString(),
            'days' => $days,
            'recurrences' => Recurrence::orderBy('title')->get()
                ->filter(fn (Recurrence $r) => ! $r->until || $r->until->gte($today))
                ->map(fn (Recurrence $r) => ['id' => $r->id, 'title' => $r->title, 'rule' => $r->describe(), 'time' => $r->start_time ? substr($r->start_time, 0, 5) : null])
                ->values(),
        ]);
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) || ! CarbonImmutable::hasFormat($value, 'Y-m-d')) {
            return null;
        }

        return CarbonImmutable::parse($value)->startOfDay();
    }
}

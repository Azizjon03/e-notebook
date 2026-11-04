<?php

namespace App\Modules\Planner;

use App\Modules\Routine\PrayerTime;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TaskController
{
    public function __construct(private readonly RecurrenceExpander $expander) {}

    public function today(Request $request): Response
    {
        $tz = $request->user()->timezone;
        $today = CarbonImmutable::now($tz)->toDateString();
        $date = $this->validDate($request->query('date')) ?? $today;
        $day = CarbonImmutable::parse($date);
        $this->expander->expand($day, $day, CarbonImmutable::parse($today));

        $tasks = Task::onDate($date)->ordered()->get();
        $overdue = $date === $today
            ? Task::whereDate('date', '<', $today)->whereNull('done_at')->count()
            : 0;

        return Inertia::render('Today', [
            'date' => $date,
            'today' => $today,
            'tasks' => $tasks->map->toCard()->values(),
            'stats' => ['done' => $tasks->whereNotNull('done_at')->count(), 'total' => $tasks->count()],
            'overdue' => $overdue,
            'prayers' => $this->prayers($request, $date),
            'now' => CarbonImmutable::now($tz)->format('H:i'),
        ]);
    }

    /**
     * @return list<array{key:string, name:string, time:string}>|null
     */
    private function prayers(Request $request, string $date): ?array
    {
        $regionId = $request->user()->region_id;
        if (! $regionId) {
            return null;
        }

        return PrayerTime::where('region_id', $regionId)->whereDate('date', $date)->first()?->toList();
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $repeat = $request->validate([
            'repeat' => ['nullable', Rule::in(Recurrence::FREQUENCIES)],
            'weekdays' => ['nullable', 'array', 'required_if:repeat,weekly'],
            'weekdays.*' => ['integer', 'between:1,7'],
            'until' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date'],
        ], ['weekdays.required_if' => 'Hafta kunlarini tanlang.']);

        if ($repeat['repeat'] ?? null) {
            Recurrence::create([
                'title' => $data['title'],
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'frequency' => $repeat['repeat'],
                'weekdays' => $repeat['repeat'] === 'weekly' ? array_map('intval', $repeat['weekdays']) : null,
                'starts_on' => $data['date'],
                'until' => $repeat['until'] ?? null,
            ]);

            $today = CarbonImmutable::now($request->user()->timezone)->startOfDay();
            $day = CarbonImmutable::parse($data['date']);
            $this->expander->expand($day, $day->addDays(6), $today);

            return back();
        }

        $data['position'] = (int) Task::onDate($data['date'])->max('position') + 1;
        Task::create($data);

        return back();
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $task->update($this->validated($request));

        return back();
    }

    public function toggle(Task $task): RedirectResponse
    {
        $task->update(['done_at' => $task->isDone() ? null : now()]);

        return back();
    }

    public function moveToTomorrow(Task $task): RedirectResponse
    {
        if (! $task->isDone()) {
            $this->detachFromRecurrence($task);
            $task->update([
                'moved_from_date' => $task->moved_from_date ?? $task->date,
                'date' => $task->date->addDay()->toDateString(),
            ]);
        }

        return back();
    }

    /**
     * O'tgan kunlardan qolgan bajarilmagan vazifalarni bugunga ko'chiradi.
     */
    public function carryOver(Request $request): RedirectResponse
    {
        $today = CarbonImmutable::now($request->user()->timezone)->toDateString();

        Task::whereDate('date', '<', $today)->whereNull('done_at')->get()
            ->each(function (Task $t) use ($today) {
                $this->detachFromRecurrence($t);
                $t->update([
                    'moved_from_date' => $t->moved_from_date ?? $t->date,
                    'date' => $today,
                ]);
            });

        return back();
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $rule = $task->recurrence;

        if ($rule && $request->query('scope') === 'all') {
            // Shu kundan boshlab takrorlanish to'xtaydi; bajarilganlar tarixda qoladi.
            $rule->update(['until' => $task->date->subDay()->toDateString()]);
            $rule->tasks()->where('date', '>=', $task->date->toDateString())->whereNull('done_at')->delete();

            return back();
        }

        $rule?->skip($task->date->toDateString());
        $task->delete();

        return back();
    }

    /**
     * Takrorlanadigan vazifa nusxasi boshqa kunga ko'chsa, u oddiy vazifaga aylanadi
     * va qoida o'sha kunni qayta yaratmaydi.
     */
    private function detachFromRecurrence(Task $task): void
    {
        if ($task->recurrence) {
            $task->recurrence->skip($task->date->toDateString());
            $task->recurrence_id = null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', Rule::when($request->filled('start_time'), 'after:start_time')],
        ], [
            'title.required' => 'Vazifa nomini yozing.',
            'end_time.after' => 'Tugash vaqti boshlanishidan keyin bo\'lsin.',
        ]);
    }

    private function validDate(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return CarbonImmutable::hasFormat($value, 'Y-m-d') ? $value : null;
    }
}

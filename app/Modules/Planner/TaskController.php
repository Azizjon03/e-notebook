<?php

namespace App\Modules\Planner;

use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TaskController
{
    public function today(Request $request): Response
    {
        $tz = $request->user()->timezone;
        $today = CarbonImmutable::now($tz)->toDateString();
        $date = $this->validDate($request->query('date')) ?? $today;

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
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
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
            ->each(fn (Task $t) => $t->update([
                'moved_from_date' => $t->moved_from_date ?? $t->date,
                'date' => $today,
            ]));

        return back();
    }

    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return back();
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

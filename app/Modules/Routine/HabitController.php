<?php

namespace App\Modules\Routine;

use App\Modules\Core\Area;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HabitController
{
    public function index(Request $request): Response
    {
        HabitDefaults::ensure($request->user());

        $today = CarbonImmutable::now($request->user()->timezone)->startOfDay();
        $from = $today->subDays(6);

        $habits = Habit::with(['area', 'entries' => fn ($q) => $q->whereBetween('date', [$from->toDateString(), $today->toDateString()])])
            ->orderBy('sort')->orderBy('id')->get();

        return Inertia::render('Habits', [
            'habits' => $habits->map(fn (Habit $h) => [
                'id' => $h->id,
                'key' => $h->key,
                'name' => $h->name,
                'kind' => $h->kind,
                'target' => $h->target,
                'weekdays' => $h->weekdays,
                'is_active' => $h->is_active,
                'area' => $h->area->key,
                'custom' => $h->key === null,
                // Oxirgi 7 kun: bajarilgan kunlar
                'week' => collect(range(6, 0))->map(fn ($i) => (bool) $h->entries
                    ->first(fn (DailyEntry $e) => $e->date->toDateString() === $today->subDays($i)->toDateString())?->done_at)->all(),
            ]),
            'areas' => Area::orderBy('sort')->get(['key', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'area' => ['required', Rule::in(array_keys(Area::ALL))],
            'kind' => ['required', Rule::in(['check', 'count', 'duration'])],
            'target' => ['nullable', 'required_unless:kind,check', 'integer', 'min:1', 'max:1000000'],
            'weekdays' => ['nullable', 'array'],
            'weekdays.*' => ['integer', 'between:1,7'],
        ], ['name.required' => 'Odat nomini yozing.', 'target.required_unless' => 'Maqsadni kiriting.']);

        Habit::create([
            'name' => $data['name'],
            'area_id' => Area::idFor($data['area']),
            'kind' => $data['kind'],
            'target' => $data['kind'] === 'check' ? null : $data['target'],
            'weekdays' => $this->weekdays($data['weekdays'] ?? null),
            'sort' => 100,
        ]);

        return back();
    }

    public function update(Request $request, Habit $habit): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'target' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000000'],
            'weekdays' => ['sometimes', 'nullable', 'array'],
            'weekdays.*' => ['integer', 'between:1,7'],
        ]);

        if (array_key_exists('weekdays', $data)) {
            $data['weekdays'] = $this->weekdays($data['weekdays']);
        }
        if ($habit->kind === 'check') {
            unset($data['target']);
        }

        $habit->update($data);

        return back();
    }

    public function destroy(Habit $habit): RedirectResponse
    {
        // Tizim odatlari o'chirilmaydi, faqat o'chirib qo'yiladi (is_active).
        abort_if($habit->key !== null, 422, 'Bu odatni faqat o\'chirib qo\'yish mumkin.');
        $habit->delete();

        return back();
    }

    /**
     * Odat belgisi: check — almashadi, count/duration — qiymat yoziladi.
     */
    public function entry(Request $request, Habit $habit): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'value' => [$habit->kind === 'check' ? 'nullable' : 'required', 'integer', 'min:0', 'max:1000000'],
        ]);

        // Kelajak kunni belgilab bo'lmaydi.
        $today = CarbonImmutable::now($request->user()->timezone)->toDateString();
        abort_if($data['date'] > $today, 422, 'Kelajak kunni belgilab bo\'lmaydi.');

        $entry = DailyEntry::firstOrNew(['habit_id' => $habit->id, 'date' => $data['date']]);

        if ($habit->kind === 'check') {
            $entry->done_at = $entry->done_at ? null : now();
        } else {
            $entry->value = (int) $data['value'];
            $entry->done_at = $habit->isReachedBy($entry->value) ? ($entry->done_at ?? now()) : null;
        }
        $entry->save();

        return back();
    }

    /**
     * @param  list<int|string>|null  $days
     * @return list<int>|null
     */
    private function weekdays(?array $days): ?array
    {
        $days = collect($days ?? [])->map(fn ($d) => (int) $d)->unique()->sort()->values()->all();

        return ($days === [] || count($days) === 7) ? null : $days;
    }
}

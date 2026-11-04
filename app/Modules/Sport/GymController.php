<?php

namespace App\Modules\Sport;

use App\Models\User;
use App\Modules\Routine\DailyEntry;
use App\Modules\Routine\Habit;
use App\Modules\Routine\HabitDefaults;
use App\Modules\Routine\HabitPoints;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Zal: foydalanuvchi faqat o'z jinsiga mos dasturlarni ko'radi va tanlaydi.
 * Tekshiruv serverda: boshqa jins dasturi hech qachon yuborilmaydi va tanlanmaydi.
 */
class GymController
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $programs = GymCatalog::forGender($user->gender);
        $program = $this->currentProgram($user);
        $today = $this->today($user);

        $workout = null;
        if ($program) {
            $existing = GymWorkout::whereDate('date', $today)->first();
            $dayIndex = $existing && $existing->program_key === $user->gym_program
                ? $existing->day_index
                : $user->gym_day_index % count($program['days']);
            $card = GymCatalog::card($user->gym_program, $program);

            $workout = [
                'day_index' => $dayIndex,
                'day' => $card['days'][$dayIndex],
                'days_count' => count($program['days']),
                'completed' => (object) ($existing?->completed_sets ?? []),
                'finished' => (bool) $existing?->finished_at,
                'scheduled_today' => in_array(CarbonImmutable::parse($today)->isoWeekday(), $program['weekdays'], true),
            ];
        }

        $history = GymWorkout::whereNotNull('finished_at')->latest('date')->limit(6)->get()
            ->map(fn (GymWorkout $w) => [
                'date' => $w->date->toDateString(),
                'day' => __(GymCatalog::PROGRAMS[$w->program_key]['days'][$w->day_index]['name'] ?? ''),
            ]);

        return Inertia::render('Gym', [
            'gender' => $user->gender,
            'programs' => collect($programs)->map(fn ($p, $key) => GymCatalog::card($key, $p))->values(),
            'current' => $program ? $user->gym_program : null,
            'workout' => $workout,
            'history' => $history,
        ]);
    }

    public function gender(Request $request): RedirectResponse
    {
        $data = $request->validate(['gender' => ['required', Rule::in(['male', 'female'])]]);
        $user = $request->user();
        abort_if($user->gender !== null, 422, __('Jins allaqachon tanlangan.'));

        $user->update(['gender' => $data['gender']]);

        return back();
    }

    public function program(Request $request): RedirectResponse
    {
        $data = $request->validate(['program' => ['required', 'string', 'max:40']]);
        $user = $request->user();
        $program = GymCatalog::find($data['program'], $user->gender);

        if (! $program) {
            throw ValidationException::withMessages(['program' => __('Bu dastur siz uchun emas.')]);
        }

        $user->update(['gym_program' => $data['program'], 'gym_day_index' => 0]);

        // Bugungi tugatilmagan mashg'ulot eski dasturdan bo'lsa olib tashlanadi.
        GymWorkout::whereDate('date', $this->today($user))->whereNull('finished_at')->delete();

        // "Zal" odati dastur kunlariga moslanadi va yoqiladi.
        HabitDefaults::ensure($user);
        Habit::where('key', 'gym')->first()?->update(['weekdays' => $program['weekdays'], 'is_active' => true]);

        return back();
    }

    public function set(Request $request): RedirectResponse
    {
        $user = $request->user();
        $program = $this->currentProgram($user) ?? abort(422, __('Avval dastur tanlang.'));
        $workout = $this->todaysWorkout($user, $program);
        abort_if((bool) $workout->finished_at, 422, __("Mashg'ulot tugatilgan."));

        $exercises = GymCatalog::PROGRAMS[$workout->program_key]['days'][$workout->day_index]['exercises'];
        $data = $request->validate([
            'exercise' => ['required', 'integer', 'min:0', 'max:'.(count($exercises) - 1)],
            'done' => ['required', 'integer', 'min:0'],
        ]);
        $max = $exercises[$data['exercise']][1];

        $sets = $workout->completed_sets ?? [];
        $sets[(string) $data['exercise']] = min($max, $data['done']);
        $workout->update(['completed_sets' => $sets]);

        return back();
    }

    public function finish(Request $request, HabitPoints $points): RedirectResponse
    {
        $user = $request->user();
        $program = $this->currentProgram($user) ?? abort(422, __('Avval dastur tanlang.'));
        $workout = $this->todaysWorkout($user, $program);

        if ($workout->finished_at) {
            return back();
        }

        $workout->update(['finished_at' => now()]);
        $user->update(['gym_day_index' => ($workout->day_index + 1) % count($program['days'])]);

        // "Zal" odati bugun bajarildi deb belgilanadi va ball beriladi.
        HabitDefaults::ensure($user);
        $habit = Habit::where('key', 'gym')->first();
        if ($habit) {
            $entry = DailyEntry::firstOrNew(['habit_id' => $habit->id, 'date' => $this->today($user)]);
            $entry->done_at ??= now();
            $entry->save();
            $points->sync($habit, $entry);
        }

        return back();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function currentProgram(User $user): ?array
    {
        return $user->gym_program ? GymCatalog::find($user->gym_program, $user->gender) : null;
    }

    private function today(User $user): string
    {
        return CarbonImmutable::now($user->timezone)->toDateString();
    }

    /**
     * @param  array<string, mixed>  $program
     */
    private function todaysWorkout(User $user, array $program): GymWorkout
    {
        $workout = GymWorkout::whereDate('date', $this->today($user))->first();

        if ($workout && $workout->program_key !== $user->gym_program && ! $workout->finished_at) {
            $workout->delete();
            $workout = null;
        }

        return $workout ?? GymWorkout::create([
            'program_key' => $user->gym_program,
            'day_index' => $user->gym_day_index % count($program['days']),
            'date' => $this->today($user),
            'completed_sets' => [],
        ]);
    }
}

<?php

namespace App\Modules\Study;

use App\Modules\Core\Area;
use App\Modules\Core\Points;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StudyController
{
    public function index(Request $request, Points $points): Response
    {
        $user = $request->user();
        $tz = $user->timezone;
        $todayStart = CarbonImmutable::now($tz)->startOfDay();

        $sessions = StudySession::with('area')
            ->where('started_at', '>=', $todayStart->subDays(6)->utc())
            ->latest('started_at')
            ->get();

        $running = $sessions->first(fn (StudySession $s) => $s->isRunning());
        $todays = $sessions->filter(fn (StudySession $s) => $s->started_at->gte($todayStart));

        $week = collect(range(6, 0))->map(function ($i) use ($sessions, $todayStart, $tz) {
            $day = $todayStart->subDays($i);

            return [
                'date' => $day->toDateString(),
                'minutes' => (int) $sessions
                    ->filter(fn (StudySession $s) => $s->started_at->timezone($tz)->isSameDay($day))
                    ->sum('minutes'),
            ];
        });

        $recent = DB::table('points_ledger')->where('user_id', $user->id)->latest('created_at')->limit(8)
            ->get(['source_type', 'reason', 'points', 'created_at']);

        return Inertia::render('Study', [
            'running' => $running ? [
                'id' => $running->id,
                'title' => $running->title,
                'area' => $running->area->key,
                'planned_minutes' => $running->planned_minutes,
                'started_at' => $running->started_at->toIso8601String(),
                'book_id' => $running->book_id,
            ] : null,
            'today' => $todays->filter(fn ($s) => ! $s->isRunning())->values()->map(fn (StudySession $s) => [
                'id' => $s->id,
                'title' => $s->title ?: $s->area->name,
                'minutes' => $s->minutes,
                'pages' => $s->pages_read,
                'points' => StudySession::pointsFor((int) $s->minutes),
                'time' => $s->started_at->timezone($tz)->format('H:i'),
            ]),
            'week' => $week,
            'level' => Points::level($points->total($user)),
            'pointsToday' => $points->today($user),
            'recent' => $recent->map(fn ($r) => [
                'label' => $this->label($r->source_type, $r->reason),
                'points' => $r->points,
                'at' => CarbonImmutable::parse($r->created_at, 'UTC')->timezone($tz)->format('d.m H:i'),
            ]),
            'areas' => Area::whereIn('key', [Area::STUDY, Area::ILM, Area::WORK])->orderBy('sort')->get(['key', 'name']),
        ]);
    }

    public function start(Request $request, SessionFinisher $finisher): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:150'],
            'area' => ['required', Rule::in([Area::STUDY, Area::ILM, Area::WORK])],
            'planned_minutes' => ['required', 'integer', 'between:5,180'],
        ]);

        // Bir vaqtda bitta sessiya: oldingisi ochiq qolgan bo'lsa yopiladi.
        StudySession::whereNull('ended_at')->get()->each(fn (StudySession $s) => $finisher->finish($s));

        StudySession::create([
            'area_id' => Area::idFor($data['area']),
            'title' => $data['title'] ?? null,
            'planned_minutes' => $data['planned_minutes'],
            'started_at' => now(),
        ]);

        return back();
    }

    public function stop(StudySession $session, SessionFinisher $finisher): RedirectResponse
    {
        if ($session->isRunning()) {
            $finisher->finish($session);
        }

        return back();
    }

    public function destroy(StudySession $session, Points $points): RedirectResponse
    {
        $points->revoke($session, 'session');
        $session->delete();

        return back();
    }

    private function label(string $type, string $reason): string
    {
        return match (true) {
            $reason === 'streak' => '7 kunlik ketma-ketlik',
            str_starts_with($reason, 'ilm_reading') => __("Ilm kitobi o'qildi"),
            str_ends_with($type, 'StudySession') => __("O'qish sessiyasi"),
            str_ends_with($type, 'DailyEntry') => __('Odat bajarildi'),
            default => __('Ball'),
        };
    }
}

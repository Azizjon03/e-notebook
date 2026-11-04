<?php

namespace App\Modules\Core;

use App\Modules\Ilm\Book;
use App\Modules\Planner\Task;
use App\Modules\Study\StudySession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController
{
    public function show(Request $request, Points $points): Response
    {
        $user = $request->user();

        return Inertia::render('Profile', [
            'profile' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'gender' => $user->gender,
                'phone' => $user->phone,
                'telegram_username' => $user->telegram_username,
                'has_telegram' => $user->telegram_id !== null,
                'region' => $user->region?->name,
                'member_since' => $user->created_at?->toDateString(),
            ],
            'level' => Points::level($points->total($user)),
            // Global "owner" scope: faqat shu foydalanuvchining yozuvlari sanaladi.
            'totals' => [
                'tasks_done' => Task::whereNotNull('done_at')->count(),
                'study_minutes' => (int) StudySession::sum('minutes'),
                'books_finished' => Book::where('status', 'finished')->count(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['nullable', 'string', 'max:60'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
        ]);
        // Jins bir marta tanlanadi: zal dasturlari shunga qarab ko'rsatiladi.
        if ($request->user()->gender) {
            unset($data['gender']);
        }

        $request->user()->update($data);

        return back()->with('success', __('Profil saqlandi.'));
    }
}

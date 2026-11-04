<?php

namespace App\Modules\Core;

use App\Modules\Geo\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController
{
    private const FIELDS = ['planning_mode', 'planning_time', 'weekly_planning_day', 'weekly_planning_time', 'prayer_reminders', 'prayer_remind_before', 'task_reminders', 'task_remind_before', 'day_summary', 'region_id'];

    public function edit(Request $request): Response
    {
        return Inertia::render('Settings', [
            'settings' => $request->user()->only(self::FIELDS),
            'regions' => Region::whereNull('parent_id')->orderBy('sort')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $time = ['required', 'date_format:H:i'];
        $data = $request->validate([
            'planning_mode' => ['required', Rule::in(['evening', 'morning', 'off'])],
            'planning_time' => $time,
            'weekly_planning_day' => ['nullable', 'integer', 'between:1,7'],
            'weekly_planning_time' => $time,
            'prayer_reminders' => ['required', 'boolean'],
            'prayer_remind_before' => ['required', 'integer', Rule::in([0, 5, 10, 15, 20, 30])],
            'task_reminders' => ['required', 'boolean'],
            'task_remind_before' => ['required', 'integer', Rule::in([0, 5, 10, 15, 30, 60])],
            'day_summary' => ['required', 'boolean'],
            'region_id' => ['required', Rule::exists('regions', 'id')->whereNull('parent_id')],
        ]);

        $request->user()->update($data);

        return back()->with('success', 'Sozlamalar saqlandi.');
    }
}

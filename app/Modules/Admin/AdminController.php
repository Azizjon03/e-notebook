<?php

namespace App\Modules\Admin;

use App\Models\User;
use App\Modules\Planner\Task;
use App\Modules\Telegram\BotAdmin;
use App\Modules\Telegram\BotTexts;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class AdminController
{
    public function dashboard(): Response
    {
        $today = CarbonImmutable::now('Asia/Tashkent')->startOfDay();

        $byRegion = User::query()
            ->leftJoin('regions', 'regions.id', '=', 'users.region_id')
            ->groupBy('regions.name')
            ->orderByDesc('total')
            ->orderBy('name')
            ->get([DB::raw("coalesce(regions.name, 'Tanlanmagan') as name"), DB::raw('count(*) as total')]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'users' => User::count(),
                'onboarded' => User::where('onboarding_step', User::ONBOARDING_DONE)->count(),
                'new_today' => User::where('created_at', '>=', $today->utc())->count(),
                'new_week' => User::where('created_at', '>=', $today->subDays(6)->utc())->count(),
                // Admin barcha foydalanuvchilar statistikasini ko'radi, shuning uchun cheklovsiz so'rov.
                'tasks_today' => Task::withoutGlobalScope('owner')->whereDate('date', $today->toDateString())->count(),
                'tasks_done_today' => Task::withoutGlobalScope('owner')->whereDate('date', $today->toDateString())->whereNotNull('done_at')->count(),
            ],
            'byRegion' => $byRegion,
            'latest' => User::with('region')->latest('id')->limit(10)->get()->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->fullName(),
                'username' => $u->telegram_username,
                'region' => $u->region?->name,
                'step' => $u->onboarding_step,
                'joined' => $u->created_at->timezone('Asia/Tashkent')->format('Y-m-d H:i'),
            ]),
        ]);
    }

    public function bot(BotAdmin $bot, BotTexts $texts): Response
    {
        return Inertia::render('Admin/Bot', [
            'status' => $bot->status(),
            'secretConfigured' => (string) config('telegram.webhook_secret') !== '',
            'texts' => $texts->all(),
            'defaults' => BotTexts::DEFAULTS,
            'labels' => BotTexts::LABELS,
            'defaultCommands' => BotAdmin::DEFAULT_COMMANDS,
        ]);
    }

    public function setWebhook(BotAdmin $bot): RedirectResponse
    {
        return $this->attempt(fn () => $bot->setWebhook(), 'Webhook o\'rnatildi.');
    }

    public function saveProfile(Request $request, BotAdmin $bot): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:512'],
            'short_description' => ['nullable', 'string', 'max:120'],
        ]);

        return $this->attempt(
            fn () => $bot->setProfile($data['name'] ?? null, $data['description'] ?? '', $data['short_description'] ?? ''),
            'Bot profili yangilandi.',
        );
    }

    public function saveCommands(Request $request, BotAdmin $bot): RedirectResponse
    {
        $data = $request->validate([
            'commands' => ['array', 'max:20'],
            'commands.*.command' => ['required', 'string', 'regex:/^[a-z0-9_]{1,32}$/'],
            'commands.*.description' => ['required', 'string', 'max:256'],
        ], [
            'commands.*.command.regex' => 'Buyruq faqat kichik lotin harflari, raqam va _ dan iborat bo\'lsin (32 belgigacha).',
        ]);

        return $this->attempt(fn () => $bot->setCommands(array_values($data['commands'] ?? [])), 'Buyruqlar saqlandi.');
    }

    public function saveTexts(Request $request, BotTexts $texts, BotAdmin $bot): RedirectResponse
    {
        $rules = collect(BotTexts::DEFAULTS)->mapWithKeys(fn ($v, $key) => [
            "texts.$key" => ['nullable', 'string', in_array($key, ['app_button', 'menu_button'], true) ? 'max:64' : 'max:2000'],
        ])->all();
        $data = $request->validate($rules);

        $texts->save($data['texts'] ?? []);

        if ($bot->isConfigured()) {
            try {
                $bot->setMenuButton();
            } catch (RuntimeException) {
                return back()->with('success', 'Matnlar saqlandi, lekin menyu tugmasini yangilab bo\'lmadi.');
            }
        }

        return back()->with('success', 'Matnlar saqlandi.');
    }

    private function attempt(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $success);
    }
}

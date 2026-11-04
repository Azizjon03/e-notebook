<?php

namespace App\Modules\Core\Dev;

use App\Models\User;
use App\Modules\Geo\Region;
use App\Modules\Routine\HabitDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Faqat kompyuterda sinash uchun: Telegramsiz kirish.
 * Marshrut faqat APP_ENV=local va DEV_LOGIN=true bo'lganda ro'yxatdan o'tadi.
 */
class DevLoginController
{
    public const TELEGRAM_IDS = ['male' => 900000001, 'female' => 900000002];

    public static function enabled(): bool
    {
        return app()->environment('local') && (bool) config('app.dev_login');
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate(['gender' => ['required', Rule::in(['male', 'female'])]]);
        $region = Region::where('name', 'Toshkent shahri')->first();

        $user = User::firstOrCreate(['telegram_id' => self::TELEGRAM_IDS[$data['gender']]], [
            'first_name' => $data['gender'] === 'male' ? 'Sinov' : 'Sinova',
            'last_name' => 'Foydalanuvchi',
            'gender' => $data['gender'],
            'region_id' => $region?->id,
            'timezone' => 'Asia/Tashkent',
            'onboarding_step' => 'done',
        ]);
        if ($request->session()->has('locale')) {
            $user->update(['locale' => $request->session()->get('locale')]);
        }
        HabitDefaults::ensure($user);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->route('today');
    }
}

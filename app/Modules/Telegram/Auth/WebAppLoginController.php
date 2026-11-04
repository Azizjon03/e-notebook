<?php

namespace App\Modules\Telegram\Auth;

use App\Models\User;
use App\Modules\Core\I18n\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class WebAppLoginController
{
    public function __invoke(Request $request, InitDataValidator $validator): RedirectResponse
    {
        $data = $request->validate([
            'init_data' => ['required', 'string', 'max:4096'],
        ]);

        try {
            $tg = $validator->validate($data['init_data']);
        } catch (InvalidInitData $e) {
            Log::notice('Telegram initData rad etildi', ['reason' => $e->getMessage()]);

            throw ValidationException::withMessages([
                'init_data' => __("Telegram orqali kirib bo'lmadi. Ilovani bot ichidan qayta oching."),
            ]);
        }

        $user = User::firstOrNew(['telegram_id' => $tg['id']]);
        $user->telegram_username = $tg['username'] ?? null;
        $user->language_code = $tg['language_code'] ?? null;
        if (! $user->exists) {
            // Ism va familiyani foydalanuvchi ro'yxatdan o'tishda o'zi tasdiqlaydi.
            $user->first_name = $tg['first_name'];
            $user->last_name = $tg['last_name'] ?? null;
            $user->locale = Locale::fromTelegram($tg['language_code'] ?? null);
        }
        // Kirish sahifasida til tanlangan bo'lsa, shu til saqlanadi.
        if ($request->session()->has('locale')) {
            $user->locale = Locale::valid($request->session()->get('locale'));
        }
        $user->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('today'));
    }
}

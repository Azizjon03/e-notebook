<?php

namespace App\Modules\Core\Demo;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoController
{
    /** Mehmonni demo hisobga kiritadi ("eslab qolish"siz). */
    public function enter(Request $request): RedirectResponse
    {
        Auth::login(DemoAccount::user());
        $request->session()->regenerate();

        return redirect()->route('today');
    }

    /** Demodan chiqib, ro'yxatdan o'tish sahifasiga. */
    public function exit(Request $request): RedirectResponse
    {
        return self::toRegister($request);
    }

    public static function toRegister(Request $request, ?string $message = null): RedirectResponse
    {
        $locale = $request->session()->get('locale');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if ($locale) {
            $request->session()->put('locale', $locale);
        }

        return redirect()->route('register')->with('error', $message);
    }
}

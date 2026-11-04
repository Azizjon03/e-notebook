<?php

namespace App\Modules\Core\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordLoginController
{
    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string'],
        ]);

        $phone = Phone::normalize($data['phone']);
        $user = $phone ? User::firstWhere('phone', $phone) : null;

        if (! $user?->password || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['phone' => __("Telefon raqam yoki parol noto'g'ri.")]);
        }

        if ($request->session()->has('locale')) {
            $user->update(['locale' => $request->session()->get('locale')]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('today'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

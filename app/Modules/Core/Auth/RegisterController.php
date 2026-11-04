<?php

namespace App\Modules\Core\Auth;

use App\Models\User;
use App\Modules\Core\I18n\Locale;
use App\Modules\Geo\Region;
use App\Modules\Routine\HabitDefaults;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController
{
    public function create(): Response
    {
        return Inertia::render('Register', [
            'regions' => Region::whereNull('parent_id')->orderBy('sort')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['phone' => Phone::normalize($request->input('phone')) ?? $request->input('phone')]);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['nullable', 'string', 'max:60'],
            'phone' => PasswordRules::phone(),
            'password' => PasswordRules::password(),
            'gender' => ['required', Rule::in(['male', 'female'])],
            'region_id' => ['required', Rule::exists('regions', 'id')->whereNull('parent_id')],
        ], PasswordRules::messages());

        $user = User::create([
            ...$data,
            'timezone' => 'Asia/Tashkent',
            'onboarding_step' => User::ONBOARDING_DONE,
            'locale' => Locale::valid($request->session()->get('locale')),
        ]);
        HabitDefaults::ensure($user);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->route('today');
    }
}

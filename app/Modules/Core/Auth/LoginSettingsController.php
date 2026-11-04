<?php

namespace App\Modules\Core\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/** Sozlamalar: telefon raqam va parolni o'rnatish yoki o'zgartirish. */
class LoginSettingsController
{
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->merge(['phone' => Phone::normalize($request->input('phone')) ?? $request->input('phone')]);

        $data = $request->validate([
            'phone' => PasswordRules::phone($user->id),
            'current_password' => ['nullable', 'string'],
            // Parol hali yo'q bo'lsa, majburiy; bo'lsa, faqat o'zgartirmoqchi bo'lganda.
            'password' => PasswordRules::password(required: ! $user->password),
        ], PasswordRules::messages());

        if ($user->password && ! Hash::check($data['current_password'] ?? '', $user->password)) {
            throw ValidationException::withMessages(['current_password' => __("Hozirgi parol noto'g'ri.")]);
        }

        $user->phone = $data['phone'];
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        return back()->with('success', __("Kirish ma'lumotlari saqlandi."));
    }
}

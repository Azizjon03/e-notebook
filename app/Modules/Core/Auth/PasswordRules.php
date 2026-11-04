<?php

namespace App\Modules\Core\Auth;

use Illuminate\Validation\Rule;

/** Telefon va parol uchun umumiy qoidalar (ro'yxatdan o'tish va Sozlamalar). */
final class PasswordRules
{
    public const MIN = 8;

    /** @return list<mixed> */
    public static function phone(?int $ignoreUserId = null): array
    {
        return ['required', 'regex:/^\+998\d{9}$/', Rule::unique('users', 'phone')->ignore($ignoreUserId)];
    }

    /** @return list<string> */
    public static function password(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'min:'.self::MIN, 'max:72', 'confirmed'];
    }

    /** @return array<string, string> */
    public static function messages(): array
    {
        return [
            'phone.regex' => __("Telefon raqamni +998 90 123 45 67 ko'rinishida yozing."),
            'phone.unique' => __("Bu telefon raqam bilan allaqachon ro'yxatdan o'tilgan."),
            'password.min' => __("Parol kamida :min belgidan iborat bo'lsin.", ['min' => self::MIN]),
            'password.confirmed' => __('Parollar bir xil emas.'),
        ];
    }
}

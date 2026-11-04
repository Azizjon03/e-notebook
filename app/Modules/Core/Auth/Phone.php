<?php

namespace App\Modules\Core\Auth;

/** O'zbekiston telefon raqami: har qanday yozuvni +998XXXXXXXXX ko'rinishiga keltiradi. */
final class Phone
{
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);
        if (strlen($digits) === 9) {
            $digits = '998'.$digits;
        }

        return preg_match('/^998\d{9}$/', $digits) ? '+'.$digits : null;
    }
}

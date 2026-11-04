<?php

namespace App\Modules\Core\I18n;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Ilova tillari. Asosiy til o'zbekcha: kalitlar o'zbekcha matnning o'zi,
 * lang/ru.json va lang/en.json ularning tarjimasi.
 */
class Locale
{
    public const ALL = ['uz' => "O'zbekcha", 'ru' => 'Русский', 'en' => 'English'];

    public const DEFAULT = 'uz';

    /** Telegram language_code dan ilova tilini tanlaydi. */
    public static function fromTelegram(?string $code): string
    {
        $code = strtolower(substr((string) $code, 0, 2));

        return match ($code) {
            'ru', 'kk', 'ky', 'tg', 'be', 'uk' => 'ru',
            'en' => 'en',
            default => self::DEFAULT,
        };
    }

    public static function valid(?string $locale): string
    {
        return array_key_exists((string) $locale, self::ALL) ? $locale : self::DEFAULT;
    }

    /**
     * @return array<string, string>
     */
    public static function dictionary(string $locale): array
    {
        if ($locale === self::DEFAULT) {
            return [];
        }

        $path = lang_path($locale.'.json');

        return Cache::remember('i18n.'.$locale.'.'.(File::exists($path) ? File::lastModified($path) : 0), 3600,
            fn () => File::exists($path) ? json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR) : []);
    }
}

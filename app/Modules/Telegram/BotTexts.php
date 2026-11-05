<?php

namespace App\Modules\Telegram;

use App\Modules\Core\I18n\Locale;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Bot xabar matnlari. Standart matnlar shu yerda, admin sahifasidan
 * o'zgartirilganlari bot_settings jadvalida saqlanadi.
 * Matnda {name} o'rniga foydalanuvchi ismi qo'yiladi.
 */
class BotTexts
{
    public const DEFAULTS = [
        'welcome' => "Assalomu alaykum! e-notebook'ga xush kelibsiz.\n\nIsm va familiyangizni yozing, masalan: <i>Ali Valiyev</i>\n\nTilni o'zgartirish: /til",
        'name_invalid' => 'Iltimos, ism va familiyani bitta xabarda yozing, masalan: <i>Ali Valiyev</i>',
        'ask_location' => "Rahmat, {name}! Namoz vaqtlari to'g'ri chiqishi uchun joylashuvingizni yuboring yoki viloyatni tanlang.",
        'ask_gender' => 'Jinsingizni tanlang.',
        'done' => 'Tayyor, {name}! Endi kunlik rejalaringizni ilovada tuzishingiz mumkin.',
        'open_app' => 'Ilovani ochish uchun tugmani bosing.',
        'app_button' => 'Ilovani ochish',
        'menu_button' => 'Ilova',
    ];

    public const LABELS = [
        'welcome' => '/start dan keyingi salomlashish',
        'name_invalid' => 'Ism noto\'g\'ri yozilganda',
        'ask_location' => 'Joylashuv so\'rash',
        'ask_gender' => 'Jins so\'rash',
        'done' => 'Ro\'yxatdan o\'tish tugaganda',
        'open_app' => 'Tayyor foydalanuvchi /start bosganda',
        'app_button' => 'Mini App tugmasi matni',
        'menu_button' => 'Chat menyusidagi tugma matni',
    ];

    private const CACHE_KEY = 'bot_texts';

    /**
     * @param  array<string, string|int>  $replace
     */
    public function get(string $key, array $replace = [], ?string $locale = null): string
    {
        $text = $this->all($locale)[$key] ?? $key;

        foreach ($replace as $name => $value) {
            $text = str_replace('{'.$name.'}', e((string) $value), $text);
        }

        return $text;
    }

    /**
     * Shu tildagi standart matnlar (lang/*.json dan tarjima).
     *
     * @return array<string, string>
     */
    public static function defaults(?string $locale = null): array
    {
        $locale = Locale::valid($locale ?? app()->getLocale());

        return array_map(fn (string $text) => __($text, [], $locale), self::DEFAULTS);
    }

    /**
     * Admin o'zgartirgan matnlar ustidan qo'yilgan to'liq ro'yxat.
     *
     * @return array<string, string>
     */
    public function all(?string $locale = null): array
    {
        $locale = Locale::valid($locale ?? app()->getLocale());
        $prefix = self::prefix($locale);

        $saved = Cache::rememberForever(self::CACHE_KEY.'.'.$locale, fn () => DB::table('bot_settings')
            ->where('key', 'like', $prefix.'%')
            ->pluck('value', 'key')
            ->mapWithKeys(fn ($v, $k) => [substr($k, strlen($prefix)) => $v])
            ->all());

        return array_merge(self::defaults($locale), array_intersect_key($saved, self::DEFAULTS));
    }

    /**
     * @param  array<string, string|null>  $texts
     */
    public function save(array $texts, ?string $locale = null): void
    {
        $locale = Locale::valid($locale ?? Locale::DEFAULT);
        $prefix = self::prefix($locale);
        $defaults = self::defaults($locale);

        foreach (array_intersect_key($texts, self::DEFAULTS) as $key => $value) {
            if ($value === null || trim($value) === '' || $value === $defaults[$key]) {
                DB::table('bot_settings')->where('key', $prefix.$key)->delete();

                continue;
            }

            DB::table('bot_settings')->updateOrInsert(
                ['key' => $prefix.$key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()],
            );
        }

        Cache::forget(self::CACHE_KEY.'.'.$locale);
    }

    /** O'zbekcha matnlar avvalgidek "text.<kalit>", boshqalari "text.<til>.<kalit>". */
    private static function prefix(string $locale): string
    {
        return $locale === Locale::DEFAULT ? 'text.' : 'text.'.$locale.'.';
    }
}

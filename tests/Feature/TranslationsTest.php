<?php

use App\Modules\Core\I18n\Locale;
use Tests\Support\TranslationKeys;

dataset('locales', ['ru', 'en']);

it('has a translation for every text in the app', function (string $locale) {
    $dict = json_decode(file_get_contents(lang_path("$locale.json")), true, flags: JSON_THROW_ON_ERROR);

    $missing = array_values(array_filter(TranslationKeys::all(), fn ($k) => ! array_key_exists($k, $dict)));

    expect($missing)->toBe([]);
})->with('locales');

it('keeps the same placeholders in translations', function (string $locale) {
    $dict = json_decode(file_get_contents(lang_path("$locale.json")), true, flags: JSON_THROW_ON_ERROR);
    $placeholders = fn (string $s) => collect([...(preg_match_all('/:[a-z]+/', $s, $m) ? $m[0] : []), ...(preg_match_all('/\{[a-z]+\}/', $s, $m2) ? $m2[0] : [])])->sort()->values()->all();

    foreach ($dict as $key => $value) {
        expect($placeholders($value))->toBe($placeholders($key), "$locale: $key");
    }
})->with('locales');

it('picks the app language from Telegram', function () {
    expect(Locale::fromTelegram('ru'))->toBe('ru')
        ->and(Locale::fromTelegram('en-US'))->toBe('en')
        ->and(Locale::fromTelegram('uz'))->toBe('uz')
        ->and(Locale::fromTelegram(null))->toBe('uz')
        ->and(Locale::fromTelegram('de'))->toBe('uz');
});

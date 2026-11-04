<?php

namespace App\Modules\Telegram\Auth;

use Carbon\CarbonImmutable;

/**
 * Telegram Mini App initData imzosini tekshiradi.
 *
 * https://core.telegram.org/bots/webapps#validating-data-received-via-the-mini-app
 * secret_key = HMAC_SHA256(bot_token, key: "WebAppData")
 * hash       = hex(HMAC_SHA256(data_check_string, key: secret_key))
 */
class InitDataValidator
{
    public function __construct(
        private readonly string $botToken,
        private readonly int $ttlSeconds = 86400,
    ) {}

    /**
     * @return array{id:int, first_name:string, last_name?:string, username?:string, language_code?:string}
     *
     * @throws InvalidInitData
     */
    public function validate(string $initData, ?CarbonImmutable $now = null): array
    {
        if ($this->botToken === '') {
            throw new InvalidInitData('Bot tokeni sozlanmagan.');
        }

        parse_str($initData, $fields);

        $hash = $fields['hash'] ?? null;
        if (! is_string($hash) || $hash === '') {
            throw new InvalidInitData('hash yo\'q.');
        }
        unset($fields['hash']);

        ksort($fields);
        $dataCheckString = collect($fields)
            ->map(fn ($value, $key) => $key.'='.$value)
            ->implode("\n");

        $secretKey = hash_hmac('sha256', $this->botToken, 'WebAppData', true);
        $expected = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (! hash_equals($expected, strtolower($hash))) {
            throw new InvalidInitData('Imzo noto\'g\'ri.');
        }

        $authDate = (int) ($fields['auth_date'] ?? 0);
        $now ??= CarbonImmutable::now();
        if ($authDate <= 0 || $now->getTimestamp() - $authDate > $this->ttlSeconds) {
            throw new InvalidInitData('initData eskirgan.');
        }

        $user = json_decode((string) ($fields['user'] ?? ''), true);
        if (! is_array($user) || ! isset($user['id'], $user['first_name'])) {
            throw new InvalidInitData('Foydalanuvchi ma\'lumoti yo\'q.');
        }

        return $user;
    }
}

<?php

namespace Tests\Support;

class TelegramInitData
{
    /**
     * Telegram qanday imzolasa, test uchun shunday initData yasaydi.
     *
     * @param  array<string, mixed>  $user
     */
    public static function make(string $botToken, array $user, ?int $authDate = null, array $extra = []): string
    {
        $fields = array_merge([
            'auth_date' => (string) ($authDate ?? time()),
            'query_id' => 'AAH-test',
            'user' => json_encode($user, JSON_UNESCAPED_UNICODE),
        ], $extra);

        ksort($fields);
        $dataCheckString = collect($fields)->map(fn ($v, $k) => $k.'='.$v)->implode("\n");
        $secret = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $fields['hash'] = hash_hmac('sha256', $dataCheckString, $secret);

        return http_build_query($fields, '', '&', PHP_QUERY_RFC3986);
    }
}

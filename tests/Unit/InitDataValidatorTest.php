<?php

use App\Modules\Telegram\Auth\InitDataValidator;
use App\Modules\Telegram\Auth\InvalidInitData;
use Tests\Support\TelegramInitData;

const TOKEN = '123456:TEST-token';

$user = ['id' => 777001, 'first_name' => 'Ali', 'last_name' => 'Valiyev', 'username' => 'ali'];

it('accepts initData signed with the bot token', function () use ($user) {
    $data = TelegramInitData::make(TOKEN, $user);

    expect((new InitDataValidator(TOKEN))->validate($data))
        ->toMatchArray(['id' => 777001, 'first_name' => 'Ali']);
});

it('rejects initData signed with another token', function () use ($user) {
    $data = TelegramInitData::make('999:other', $user);

    (new InitDataValidator(TOKEN))->validate($data);
})->throws(InvalidInitData::class, 'Imzo');

it('rejects tampered user data', function () use ($user) {
    $data = TelegramInitData::make(TOKEN, $user);
    $tampered = str_replace('777001', '777002', $data);

    (new InitDataValidator(TOKEN))->validate($tampered);
})->throws(InvalidInitData::class, 'Imzo');

it('rejects initData older than 24 hours', function () use ($user) {
    $data = TelegramInitData::make(TOKEN, $user, authDate: time() - 86401);

    (new InitDataValidator(TOKEN))->validate($data);
})->throws(InvalidInitData::class, 'eskirgan');

it('rejects initData without a hash', function () {
    (new InitDataValidator(TOKEN))->validate('auth_date='.time());
})->throws(InvalidInitData::class);

it('refuses to work without a bot token', function () use ($user) {
    (new InitDataValidator(''))->validate(TelegramInitData::make(TOKEN, $user));
})->throws(InvalidInitData::class, 'sozlanmagan');

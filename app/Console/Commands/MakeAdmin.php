<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'user:make-admin {telegram_id : Foydalanuvchining Telegram ID raqami} {--revoke : Adminlikni olib tashlash}';

    protected $description = 'Foydalanuvchini admin qiladi (yoki --revoke bilan adminlikni oladi)';

    public function handle(): int
    {
        $user = User::firstWhere('telegram_id', $this->argument('telegram_id'));

        if (! $user) {
            $this->error('Bunday foydalanuvchi yo\'q. Avval botga /start yozsin.');

            return self::FAILURE;
        }

        $user->update(['is_admin' => ! $this->option('revoke')]);
        $this->info($user->fullName().($user->is_admin ? ' endi admin.' : ' endi admin emas.'));

        return self::SUCCESS;
    }
}

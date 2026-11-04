<?php

namespace App\Modules\Core\Reminders;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DispatchReminders extends Command
{
    protected $signature = 'reminders:dispatch';

    protected $description = 'Vaqti kelgan eslatmalarni topib, Telegram navbatiga qo\'yadi (har daqiqa)';

    public function handle(ReminderPlanner $planner): int
    {
        $now = CarbonImmutable::now('UTC');
        $count = 0;

        User::where('onboarding_step', User::ONBOARDING_DONE)->chunkById(200, function ($users) use ($planner, $now, &$count) {
            foreach ($users as $user) {
                foreach ($planner->due($user, $now) as $reminder) {
                    // Avval log: ikki parallel ishga tushishda ham bitta eslatma bir marta ketadi.
                    $inserted = DB::table('reminder_logs')->insertOrIgnore([
                        'user_id' => $user->id,
                        'ref' => $reminder->ref,
                        'sent_at' => $now,
                    ]);

                    if ($inserted) {
                        SendReminder::dispatch($user->telegram_id, $reminder->text, $reminder->replyMarkup);
                        $count++;
                    }
                }
            }
        });

        $this->line("Navbatga qo'yildi: {$count}");

        return self::SUCCESS;
    }
}

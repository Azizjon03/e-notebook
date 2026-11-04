<?php

namespace App\Modules\Routine;

use App\Models\User;
use App\Modules\Geo\Region;
use App\Modules\Telegram\TelegramClient;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class FetchPrayerTimes extends Command
{
    protected $signature = 'prayer-times:fetch {--month= : YYYY-MM, standart: joriy oy} {--next : Keyingi oyni yuklash}';

    protected $description = 'Har bir viloyat uchun bir oylik namoz vaqtlarini yuklaydi';

    public function handle(PrayerTimeSource $source, TelegramClient $telegram): int
    {
        $month = $this->option('month')
            ? CarbonImmutable::createFromFormat('Y-m-d', $this->option('month').'-01', 'Asia/Tashkent')
            : CarbonImmutable::now('Asia/Tashkent')->startOfMonth();
        if ($this->option('next')) {
            $month = $month->addMonthNoOverflow();
        }

        $failed = [];
        foreach (Region::whereNull('parent_id')->orderBy('sort')->get() as $region) {
            try {
                $days = $source->month($region, $month->year, $month->month);
            } catch (Throwable $e) {
                // Manba ishlamasa eski ma'lumot saqlanib qoladi.
                $failed[] = $region->name;
                $this->warn($e->getMessage());

                continue;
            }

            foreach ($days as $date => $times) {
                PrayerTime::updateOrCreate(['region_id' => $region->id, 'date' => $date], $times);
            }
            $this->line("{$region->name}: ".count($days).' kun');
        }

        if ($failed) {
            $this->notifyAdmins($telegram, $month, $failed);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $failed
     */
    private function notifyAdmins(TelegramClient $telegram, CarbonImmutable $month, array $failed): void
    {
        $text = "Namoz vaqtlarini yuklab bo'lmadi ({$month->format('Y-m')}): ".implode(', ', $failed);

        User::where('is_admin', true)->whereNotNull('telegram_id')->each(function (User $admin) use ($telegram, $text) {
            try {
                $telegram->sendMessage($admin->telegram_id, $text);
            } catch (Throwable) {
                // Token sozlanmagan bo'lishi mumkin; xato allaqachon konsolda.
            }
        });
    }
}

<?php

namespace Tests\Support;

use App\Modules\Core\Area;
use App\Modules\Onboarding\OnboardingFlow;
use App\Modules\Routine\PrayerTime;
use App\Modules\Sport\GymCatalog;
use App\Modules\Telegram\BotAdmin;
use App\Modules\Telegram\BotTexts;
use Database\Seeders\RegionSeeder;
use Symfony\Component\Finder\Finder;

/**
 * Ilovadagi barcha tarjima kalitlari: kodda __() va t()/tk() ga yozilgan matnlar
 * hamda ro'yxatlardan tarjima qilinadigan nomlar.
 */
class TranslationKeys
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $keys = [
            ...self::scan(base_path('app'), '*.php', '/\b__\(\s*(\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*")/'),
            ...self::scan(base_path('resources/js'), '*.js*', '/\bt[k]?\(\s*(\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*")/'),
            ...array_values(BotTexts::DEFAULTS),
            ...array_values(PrayerTime::NAMES),
            ...array_values(Area::ALL),
            ...array_column(RegionSeeder::REGIONS, 0),
            ...array_column(BotAdmin::DEFAULT_COMMANDS, 'description'),
            OnboardingFlow::PICK_REGION,
            'Qadam',
            'Zal',
        ];

        foreach (GymCatalog::PROGRAMS as $p) {
            array_push($keys, $p['name'], $p['level'], $p['about']);
            foreach ($p['days'] as $d) {
                $keys[] = $d['name'];
                foreach ($d['exercises'] as $e) {
                    array_push($keys, $e[0], $e[2]);
                }
            }
        }

        $keys = array_filter($keys, fn ($k) => is_string($k) && preg_match('/\p{L}/u', $k));

        return array_values(array_unique($keys));
    }

    /**
     * @return list<string>
     */
    private static function scan(string $dir, string $pattern, string $regex): array
    {
        $out = [];
        foreach (Finder::create()->files()->in($dir)->name($pattern)->notPath('Pages/Admin')->notName('AdminLayout.jsx') as $file) {
            if (str_contains($file->getPathname(), '/Admin/')) {
                continue;
            }
            preg_match_all($regex, $file->getContents(), $m);
            foreach ($m[1] as $literal) {
                $quote = $literal[0];
                $body = substr($literal, 1, -1);
                $out[] = $quote === '"' && str_ends_with($file->getFilename(), '.php')
                    ? stripcslashes($body)
                    : str_replace(['\\\'', '\\"', '\\\\'], ['\'', '"', '\\'], $body);
            }
        }

        return $out;
    }
}

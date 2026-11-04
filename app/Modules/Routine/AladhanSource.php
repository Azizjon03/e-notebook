<?php

namespace App\Modules\Routine;

use App\Modules\Geo\Region;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AladhanSource implements PrayerTimeSource
{
    private const FIELDS = [
        'fajr' => 'Fajr',
        'sunrise' => 'Sunrise',
        'dhuhr' => 'Dhuhr',
        'asr' => 'Asr',
        'maghrib' => 'Maghrib',
        'isha' => 'Isha',
    ];

    public function month(Region $region, int $year, int $month): array
    {
        $config = config('prayer.aladhan');

        $response = Http::timeout(20)->retry(2, 1000, throw: false)
            ->get("{$config['url']}/{$year}/{$month}", [
                'latitude' => $region->latitude,
                'longitude' => $region->longitude,
                'method' => $config['method'],
                'school' => $config['school'],
                'timezonestring' => $region->timezone,
            ]);

        if (! $response->successful() || ! is_array($response->json('data'))) {
            throw new RuntimeException("Aladhan javob bermadi ({$region->name}): HTTP {$response->status()}");
        }

        $days = [];
        foreach ($response->json('data') as $day) {
            // "04-11-2026" -> "2026-11-04"
            [$d, $m, $y] = explode('-', $day['date']['gregorian']['date']);
            $days["{$y}-{$m}-{$d}"] = collect(self::FIELDS)
                ->map(fn ($field) => substr((string) $day['timings'][$field], 0, 5)) // "05:41 (+05)" -> "05:41"
                ->all();
        }

        return $days;
    }
}

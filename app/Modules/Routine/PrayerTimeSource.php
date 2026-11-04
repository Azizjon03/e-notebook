<?php

namespace App\Modules\Routine;

use App\Modules\Geo\Region;

interface PrayerTimeSource
{
    /**
     * Bir oylik vaqtlar: ['2026-11-01' => ['fajr' => '05:41', ...], ...]
     *
     * @return array<string, array<string, string>>
     */
    public function month(Region $region, int $year, int $month): array;
}

<?php

namespace App\Modules\Routine;

use Illuminate\Database\Eloquent\Model;

class PrayerTime extends Model
{
    public const NAMES = [
        'fajr' => 'Bomdod',
        'sunrise' => 'Quyosh',
        'dhuhr' => 'Peshin',
        'asr' => 'Asr',
        'maghrib' => 'Shom',
        'isha' => 'Xufton',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['date' => 'date:Y-m-d'];
    }

    /**
     * @return list<array{key:string, name:string, time:string}>
     */
    public function toList(): array
    {
        return collect(self::NAMES)
            ->map(fn ($name, $key) => ['key' => $key, 'name' => $name, 'time' => $this->{$key}])
            ->values()
            ->all();
    }
}

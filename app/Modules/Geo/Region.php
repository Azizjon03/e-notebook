<?php

namespace App\Modules\Geo;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $guarded = [];

    /** Nom bazada o'zbekcha saqlanadi, chiqishda joriy tilga tarjima qilinadi. */
    protected function name(): Attribute
    {
        return Attribute::get(fn (string $value) => __($value));
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * Koordinataga eng yaqin viloyat (Haversine). Viloyatlar soni oz, shuning
     * uchun hisob PHP'da qilinadi va har qanday bazada bir xil ishlaydi.
     */
    public static function nearestTo(float $lat, float $lng): ?self
    {
        return static::whereNull('parent_id')->get()
            ->sortBy(fn (self $r) => self::distanceKm($lat, $lng, $r->latitude, $r->longitude))
            ->first();
    }

    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}

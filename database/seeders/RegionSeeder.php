<?php

namespace Database\Seeders;

use App\Modules\Geo\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * O'zbekiston viloyatlari, koordinata markaz shahar bo'yicha.
     */
    public const REGIONS = [
        ['Toshkent shahri', 41.299500, 69.240100],
        ['Toshkent viloyati', 41.016700, 69.341700],
        ['Andijon viloyati', 40.782100, 72.344200],
        ['Farg\'ona viloyati', 40.386400, 71.786400],
        ['Namangan viloyati', 40.998300, 71.672600],
        ['Samarqand viloyati', 39.654200, 66.959700],
        ['Buxoro viloyati', 39.774700, 64.428600],
        ['Navoiy viloyati', 40.084400, 65.379200],
        ['Qashqadaryo viloyati', 38.860600, 65.789100],
        ['Surxondaryo viloyati', 37.224200, 67.278300],
        ['Jizzax viloyati', 40.115800, 67.842200],
        ['Sirdaryo viloyati', 40.489700, 68.784200],
        ['Xorazm viloyati', 41.550000, 60.633300],
        ['Qoraqalpog\'iston Respublikasi', 42.460000, 59.610000],
    ];

    public function run(): void
    {
        foreach (self::REGIONS as $i => [$name, $lat, $lng]) {
            Region::updateOrCreate(
                ['name' => $name, 'parent_id' => null],
                ['latitude' => $lat, 'longitude' => $lng, 'timezone' => 'Asia/Tashkent', 'sort' => $i + 1],
            );
        }
    }
}

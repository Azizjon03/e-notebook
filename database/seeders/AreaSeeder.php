<?php

namespace Database\Seeders;

use App\Modules\Core\Area;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $i = 0;
        foreach (Area::ALL as $key => $name) {
            Area::updateOrCreate(['key' => $key], ['name' => $name, 'sort' => ++$i]);
        }
    }
}

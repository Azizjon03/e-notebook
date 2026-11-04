<?php

use Database\Seeders\AreaSeeder;
use Illuminate\Database\Migrations\Migration;

// Yo'nalishlar ilova ishlashi uchun shart, shuning uchun migratsiya bilan qo'shiladi.
return new class extends Migration
{
    public function up(): void
    {
        (new AreaSeeder)->run();
    }

    public function down(): void {}
};

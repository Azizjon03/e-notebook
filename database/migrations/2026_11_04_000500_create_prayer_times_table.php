<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prayer_times', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('fajr', 5);
            $table->string('sunrise', 5);
            $table->string('dhuhr', 5);
            $table->string('asr', 5);
            $table->string('maghrib', 5);
            $table->string('isha', 5);
            $table->timestamps();

            $table->unique(['region_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prayer_times');
    }
};

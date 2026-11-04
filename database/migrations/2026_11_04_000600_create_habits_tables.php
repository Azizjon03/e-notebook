<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // routine | sport | study | ilm | work
            $table->string('name');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('habits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->constrained();
            // Tizim odatlari uchun kalit (fajr, dhuhr, asr, maghrib, isha, steps, gym); foydalanuvchi odatida null.
            $table->string('key')->nullable();
            $table->string('name', 100);
            $table->string('kind', 10); // check | count | duration
            $table->unsignedInteger('target')->nullable(); // count: 10000 qadam, duration: daqiqa
            $table->json('weekdays')->nullable(); // null = har kuni; [1,3,5] = Du, Cho, Ju
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'key']);
        });

        Schema::create('daily_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('habit_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('value')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->unique(['habit_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_entries');
        Schema::dropIfExists('habits');
        Schema::dropIfExists('areas');
    }
};

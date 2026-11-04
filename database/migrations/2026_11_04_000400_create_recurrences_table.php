<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('frequency', 10); // daily | weekly | monthly
            $table->json('weekdays')->nullable(); // weekly: [1..7], 1 = dushanba (ISO)
            $table->date('starts_on');
            $table->date('until')->nullable();
            $table->json('skipped_dates')->nullable(); // bitta nusxa o'chirilgan kunlar
            $table->timestamps();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->unique(['recurrence_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', fn (Blueprint $table) => $table->dropUnique(['recurrence_id', 'date']));
        Schema::dropIfExists('recurrences');
    }
};

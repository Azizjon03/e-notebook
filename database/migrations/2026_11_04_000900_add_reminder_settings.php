<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Kunlik rejalashtirish: kechqurun (ertangi kunni) yoki ertalab (bugungi kunni).
            $table->string('planning_mode', 8)->default('evening'); // evening | morning | off
            $table->string('planning_time', 5)->default('22:00');
            // Haftalik reja: hafta kuni (1 = Du ... 7 = Ya) va vaqti; null = o'chiq.
            $table->unsignedTinyInteger('weekly_planning_day')->nullable()->default(7);
            $table->string('weekly_planning_time', 5)->default('20:00');
            $table->boolean('prayer_reminders')->default(true);
            $table->unsignedSmallInteger('prayer_remind_before')->default(10);
            $table->boolean('task_reminders')->default(true);
            $table->unsignedSmallInteger('task_remind_before')->default(15);
        });

        // Yuborilgan eslatmalar: bir eslatma ikki marta ketmasligi uchun.
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ref', 80); // prayer:fajr:2026-11-04, task:12, plan:2026-11-05, plan-again:2026-11-05
            $table->timestamp('sent_at');

            $table->unique(['user_id', 'ref']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['planning_mode', 'planning_time', 'weekly_planning_day', 'weekly_planning_time', 'prayer_reminders', 'prayer_remind_before', 'task_reminders', 'task_remind_before']);
        });
    }
};

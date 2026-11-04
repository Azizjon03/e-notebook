<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "sessions" nomi Laravel sessiyalari uchun band, shuning uchun study_sessions.
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->constrained();
            $table->string('title', 150)->nullable();
            $table->unsignedSmallInteger('planned_minutes')->default(50);
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->unsignedSmallInteger('minutes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'started_at']);
        });

        Schema::create('points_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id');
            $table->string('reason', 60);
            $table->integer('points');
            $table->timestamp('created_at');

            // Bitta manba uchun bitta sabab bilan ball ikki marta yozilmaydi.
            $table->unique(['source_type', 'source_id', 'reason']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_ledger');
        Schema::dropIfExists('study_sessions');
    }
};

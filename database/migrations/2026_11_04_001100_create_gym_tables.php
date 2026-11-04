<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('gym_program', 40)->nullable();
            $table->unsignedSmallInteger('gym_day_index')->default(0); // dasturdagi navbatdagi kun
        });

        Schema::create('gym_workouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('program_key', 40);
            $table->unsignedSmallInteger('day_index');
            $table->date('date');
            $table->json('completed_sets')->nullable(); // {"0": 2, "1": 3} — mashq raqami => bajarilgan setlar
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gym_workouts');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['gym_program', 'gym_day_index']);
        });
    }
};

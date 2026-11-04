<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Yo'nalish (areas) 7-vazifada, takrorlanish 5-vazifada, guruh 2-bosqichda bog'lanadi.
            $table->unsignedBigInteger('area_id')->nullable();
            $table->unsignedBigInteger('recurrence_id')->nullable();
            $table->unsignedBigInteger('group_id')->nullable()->index();
            $table->string('title', 200);
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->date('moved_from_date')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};

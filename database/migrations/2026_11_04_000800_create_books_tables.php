<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->constrained(); // ilm yoki study
            $table->string('title', 200);
            $table->string('author', 150)->nullable();
            $table->string('stage', 100)->nullable(); // masalan: "1-bosqich: asoslar"
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('total_pages')->nullable();
            $table->unsignedInteger('current_page')->default(0);
            $table->string('status', 10)->default('planned'); // planned | reading | finished
            $table->date('started_on')->nullable();
            $table->date('finished_on')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'position']);
        });

        Schema::create('reading_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('from_page');
            $table->unsignedInteger('to_page');
            $table->string('chapter', 150)->nullable();
            $table->timestamps();

            $table->index(['book_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reading_logs');
        Schema::dropIfExists('books');
    }
};

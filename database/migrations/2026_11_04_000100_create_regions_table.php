<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Hozircha faqat viloyatlar; shaharlar keyin parent_id bilan qo'shiladi.
            $table->foreignId('parent_id')->nullable()->constrained('regions');
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);
            $table->string('timezone')->default('Asia/Tashkent');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};

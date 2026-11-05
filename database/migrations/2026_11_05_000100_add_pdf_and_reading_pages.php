<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('pdf_path')->nullable();
            $table->string('pdf_name', 200)->nullable();
            $table->unsignedInteger('pdf_size')->nullable();
            // PDF o'quvchida oxirgi ochilgan bet (orqaga qaytilsa ham current_page kamaymaydi).
            $table->unsignedInteger('last_viewed_page')->nullable();
        });

        Schema::table('study_sessions', function (Blueprint $table) {
            $table->foreignId('book_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('start_page')->nullable();
            $table->unsignedInteger('end_page')->nullable();
            $table->unsignedInteger('pages_read')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('book_id');
            $table->dropColumn(['start_page', 'end_page', 'pages_read']);
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['pdf_path', 'pdf_name', 'pdf_size', 'last_viewed_page']);
        });
    }
};

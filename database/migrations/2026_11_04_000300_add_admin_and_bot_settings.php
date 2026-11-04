<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
        });

        // Admin sahifasidan o'zgartiriladigan bot sozlamalari (xabar matnlari va h.k.).
        // Bot tokeni bu yerda SAQLANMAYDI, u faqat .env da.
        Schema::create('bot_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_settings');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Telefon va parol bilan kirish: Telegramsiz ro'yxatdan o'tganlarda telegram_id bo'lmaydi.
            $table->unsignedBigInteger('telegram_id')->nullable()->change();
            $table->string('phone', 13)->nullable()->unique(); // +998XXXXXXXXX
            $table->string('password')->nullable();
            // Umumiy demo hisob: faqat ko'rish mumkin, o'zgartirish ro'yxatdan o'tishni talab qiladi.
            $table->boolean('is_demo')->default(false)->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropColumn(['phone', 'password', 'is_demo']);
        });
    }
};

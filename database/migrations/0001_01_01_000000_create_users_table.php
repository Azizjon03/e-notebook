<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // Kirish Telegram orqali; telefon va parol 001300 migratsiyada qo'shilgan.
            $table->unsignedBigInteger('telegram_id')->unique();
            $table->string('telegram_username')->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('gender', 6)->nullable(); // male | female (zal dasturlari uchun)
            $table->string('language_code', 8)->nullable();
            // Mintaqa (viloyat/shahar) 3-vazifada regions jadvali bilan bog'lanadi.
            $table->unsignedBigInteger('region_id')->nullable()->index();
            $table->decimal('latitude', 9, 6)->nullable();
            $table->decimal('longitude', 9, 6)->nullable();
            $table->string('timezone')->default('Asia/Tashkent');
            $table->string('onboarding_step')->default('name'); // name | location | gender | done
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};

<?php

use App\Modules\Routine\PrayerTime;
use Illuminate\Support\Facades\Schedule;

// Har oy 25-kuni keyingi oy, har kuni ertalab esa joriy oy (yetishmagan bo'lsa) yuklanadi.
Schedule::command('prayer-times:fetch --next')->monthlyOn(25, '03:00')->timezone('Asia/Tashkent');
Schedule::command('prayer-times:fetch')->dailyAt('03:30')->timezone('Asia/Tashkent')
    ->when(fn () => PrayerTime::whereDate('date', now('Asia/Tashkent')->toDateString())->doesntExist());

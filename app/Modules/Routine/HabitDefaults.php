<?php

namespace App\Modules\Routine;

use App\Models\User;
use App\Modules\Core\Area;

/**
 * Yangi foydalanuvchi uchun standart odatlar: 5 vaqt namoz va 10 000 qadam yoqilgan,
 * zal (Du/Cho/Ju) o'chirilgan holda turadi, foydalanuvchi o'zi yoqadi.
 */
class HabitDefaults
{
    public static function ensure(User $user): void
    {
        if (Habit::withoutGlobalScope('owner')->where('user_id', $user->id)->exists()) {
            return;
        }

        $routine = Area::idFor(Area::ROUTINE);
        $sport = Area::idFor(Area::SPORT);

        $defaults = [];
        foreach (Habit::PRAYER_KEYS as $i => $key) {
            $defaults[] = ['key' => $key, 'name' => PrayerTime::NAMES[$key], 'area_id' => $routine, 'kind' => 'check', 'sort' => $i + 1];
        }
        $defaults[] = ['key' => 'steps', 'name' => 'Qadam', 'area_id' => $sport, 'kind' => 'count', 'target' => 10000, 'sort' => 10];
        $defaults[] = ['key' => 'gym', 'name' => 'Zal', 'area_id' => $sport, 'kind' => 'check', 'weekdays' => [1, 3, 5], 'is_active' => false, 'sort' => 11];

        foreach ($defaults as $habit) {
            Habit::create(['user_id' => $user->id] + $habit);
        }
    }
}

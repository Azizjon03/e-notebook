<?php

// Ball qoidalari. Kodni o'zgartirmasdan shu yerda sozlanadi; tarix points_ledger da saqlanadi.
return [
    'prayer' => 2,          // bitta namoz
    'gym' => 5,             // zal kuni
    'steps' => 3,           // qadam maqsadiga yetildi
    'custom_habit' => 1,    // foydalanuvchi o'z odati
    'ilm_reading' => 5,     // ilm kitobidan kunlik o'qish (9-vazifa)
    'study_full' => 10,     // to'liq o'qish sessiyasi
    'study_full_minutes' => 45, // shuncha daqiqadan keyin sessiya "to'liq" hisoblanadi
    'study_per_5_minutes' => 1, // to'liq bo'lmagan sessiya: har 5 daqiqaga
    'streak_days' => 7,
    'streak_bonus' => 20,   // bir odatda 7 kunlik ketma-ketlik

    // Daraja: 1-darajaga 100 ball, keyingilari 1.5 barobar ko'p.
    'level_base' => 100,
    'level_factor' => 1.5,
];

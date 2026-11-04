<?php

return [
    // Namoz vaqtlari manbai. Hozircha Aladhan API; islom.uz jadvali bilan solishtirib tasdiqlanadi.
    'source' => env('PRAYER_SOURCE', 'aladhan'),

    'aladhan' => [
        'url' => 'https://api.aladhan.com/v1/calendar',
        // Hisoblash usuli (https://aladhan.com/calculation-methods). 3 = Muslim World League.
        'method' => (int) env('PRAYER_METHOD', 3),
        // Asr: 1 = Hanafiy.
        'school' => 1,
    ],
];

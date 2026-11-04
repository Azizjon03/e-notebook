<?php

namespace App\Modules\Sport;

/**
 * Zal dasturlari. Har bir dastur faqat bitta jins uchun: foydalanuvchi faqat
 * o'z jinsiga mos dasturlarni ko'radi va tanlay oladi.
 *
 * Mashq: [nomi, setlar, takrorlar, dam olish soniyasi].
 */
class GymCatalog
{
    public const PROGRAMS = [
        // ---------- Erkaklar ----------
        'm_full_3' => [
            'gender' => 'male',
            'name' => "To'liq tana, 3 kun",
            'level' => "Boshlang'ich",
            'recommended' => true,
            'weekdays' => [1, 3, 5],
            'about' => "Har mashg'ulotda butun tana ishlaydi. Zalga endi kelganlar uchun eng yaxshi boshlanish.",
            'days' => [
                ['name' => 'A kuni', 'exercises' => [
                    ['Squat (shtanga bilan o\'tirib turish)', 3, '8-10', 120],
                    ['Shtanga bilan jim (bench press)', 3, '8-10', 120],
                    ['Gantel bilan eguvchi tortish (row)', 3, '10-12', 90],
                    ['Tik turib yelka jimi', 3, '10-12', 90],
                    ['Plank', 3, '30-45 s', 60],
                ]],
                ['name' => 'B kuni', 'exercises' => [
                    ['Rumincha o\'lik tortish', 3, '8-10', 120],
                    ['Qiya skameykada gantel jimi', 3, '10-12', 90],
                    ['Yuqori blokni ko\'krakka tortish', 3, '10-12', 90],
                    ['Hujum (lunge), gantel bilan', 3, '10 har oyoqqa', 90],
                    ['Osilib oyoq ko\'tarish', 3, '10-12', 60],
                ]],
            ],
        ],
        'm_upper_lower_4' => [
            'gender' => 'male',
            'name' => 'Yuqori / pastki, 4 kun',
            'level' => "O'rta",
            'recommended' => false,
            'weekdays' => [1, 2, 4, 5],
            'about' => 'Haftada 4 kun: ikki kun tananing yuqori qismi, ikki kun oyoqlar.',
            'days' => [
                ['name' => 'Yuqori 1', 'exercises' => [
                    ['Shtanga bilan jim (bench press)', 4, '6-8', 150],
                    ['Shtanga bilan eguvchi tortish', 4, '6-8', 150],
                    ['Tik turib yelka jimi', 3, '8-10', 90],
                    ['Turnikda tortilish', 3, 'maksimal', 90],
                    ['Biseps uchun gantel', 3, '10-12', 60],
                ]],
                ['name' => 'Pastki 1', 'exercises' => [
                    ['Squat (shtanga bilan)', 4, '6-8', 150],
                    ['Rumincha o\'lik tortish', 3, '8-10', 120],
                    ['Oyoq jimi (leg press)', 3, '10-12', 90],
                    ['Boldir ko\'tarish', 4, '12-15', 60],
                ]],
                ['name' => 'Yuqori 2', 'exercises' => [
                    ['Qiya skameykada gantel jimi', 4, '8-10', 120],
                    ['Yuqori blokni ko\'krakka tortish', 4, '8-10', 90],
                    ['Yon tomonga gantel ko\'tarish', 3, '12-15', 60],
                    ['Brusda otjimaniya', 3, '8-12', 90],
                    ['Triseps uchun blok', 3, '10-12', 60],
                ]],
                ['name' => 'Pastki 2', 'exercises' => [
                    ['O\'lik tortish (deadlift)', 3, '5', 180],
                    ['Bolgarcha split-squat', 3, '8-10 har oyoqqa', 90],
                    ['Oyoqni bukish (leg curl)', 3, '10-12', 60],
                    ['Plank', 3, '45-60 s', 60],
                ]],
            ],
        ],
        'm_ppl_6' => [
            'gender' => 'male',
            'name' => 'Itarish / tortish / oyoq (PPL), 6 kun',
            'level' => 'Yuqori',
            'recommended' => false,
            'weekdays' => [1, 2, 3, 4, 5, 6],
            'about' => 'Tajribali sportchilar uchun: har mushak guruhi haftada ikki marta.',
            'days' => [
                ['name' => 'Itarish', 'exercises' => [
                    ['Shtanga bilan jim (bench press)', 4, '6-8', 150],
                    ['Tik turib yelka jimi', 3, '8-10', 120],
                    ['Qiya skameykada gantel jimi', 3, '10-12', 90],
                    ['Yon tomonga gantel ko\'tarish', 4, '12-15', 60],
                    ['Triseps uchun blok', 3, '10-12', 60],
                ]],
                ['name' => 'Tortish', 'exercises' => [
                    ['O\'lik tortish (deadlift)', 3, '5', 180],
                    ['Turnikda tortilish', 4, 'maksimal', 120],
                    ['Gantel bilan eguvchi tortish (row)', 3, '10-12', 90],
                    ['Yuzga tortish (face pull)', 3, '12-15', 60],
                    ['Biseps uchun shtanga', 3, '10-12', 60],
                ]],
                ['name' => 'Oyoq', 'exercises' => [
                    ['Squat (shtanga bilan)', 4, '6-8', 150],
                    ['Rumincha o\'lik tortish', 3, '8-10', 120],
                    ['Oyoq jimi (leg press)', 3, '10-12', 90],
                    ['Oyoqni bukish (leg curl)', 3, '10-12', 60],
                    ['Boldir ko\'tarish', 4, '12-15', 60],
                ]],
            ],
        ],
        'm_home_3' => [
            'gender' => 'male',
            'name' => 'Uyda, 3 kun',
            'level' => "Boshlang'ich",
            'recommended' => false,
            'weekdays' => [1, 3, 5],
            'about' => 'Zalsiz: o\'z vazn bilan. Turnik bo\'lsa yanada yaxshi.',
            'days' => [
                ['name' => 'Uy mashqi', 'exercises' => [
                    ['Otjimaniya', 4, 'maksimal', 90],
                    ['O\'tirib turish (squat)', 4, '15-20', 90],
                    ['Turnikda tortilish yoki stol ostida tortilish', 4, 'maksimal', 90],
                    ['Hujum (lunge)', 3, '12 har oyoqqa', 60],
                    ['Plank', 3, '45-60 s', 60],
                ]],
            ],
        ],

        // ---------- Ayollar ----------
        'f_full_3' => [
            'gender' => 'female',
            'name' => "To'liq tana, 3 kun",
            'level' => "Boshlang'ich",
            'recommended' => true,
            'weekdays' => [1, 3, 5],
            'about' => 'Butun tana uchun muvozanatli dastur. Zalga endi kelganlar uchun.',
            'days' => [
                ['name' => 'A kuni', 'exercises' => [
                    ['Gantel bilan goblet squat', 3, '10-12', 90],
                    ['Gantel bilan jim', 3, '10-12', 90],
                    ['Yuqori blokni ko\'krakka tortish', 3, '10-12', 90],
                    ['Glute bridge (dumba ko\'prigi)', 3, '12-15', 60],
                    ['Plank', 3, '30-45 s', 60],
                ]],
                ['name' => 'B kuni', 'exercises' => [
                    ['Rumincha o\'lik tortish, gantel bilan', 3, '10-12', 90],
                    ['O\'tirib blok tortish (seated row)', 3, '10-12', 90],
                    ['Hujum (lunge)', 3, '10 har oyoqqa', 60],
                    ['O\'tirib yelka jimi, gantel bilan', 3, '10-12', 60],
                    ['Velosiped (press uchun)', 3, '20', 60],
                ]],
            ],
        ],
        'f_lower_4' => [
            'gender' => 'female',
            'name' => 'Pastki tana urg\'usi, 4 kun',
            'level' => "O'rta",
            'recommended' => false,
            'weekdays' => [1, 2, 4, 5],
            'about' => 'Oyoq va dumba mushaklariga ko\'proq urg\'u, ikki kun yuqori tana.',
            'days' => [
                ['name' => 'Pastki 1', 'exercises' => [
                    ['Squat (shtanga yoki gantel bilan)', 4, '8-10', 120],
                    ['Hip thrust', 4, '10-12', 90],
                    ['Oyoqni bukish (leg curl)', 3, '12-15', 60],
                    ['Oyoqni yonga ochish (abduktor)', 3, '15', 60],
                ]],
                ['name' => 'Yuqori 1', 'exercises' => [
                    ['Gantel bilan jim', 3, '10-12', 90],
                    ['Yuqori blokni ko\'krakka tortish', 3, '10-12', 90],
                    ['Yon tomonga gantel ko\'tarish', 3, '12-15', 60],
                    ['Plank', 3, '45 s', 60],
                ]],
                ['name' => 'Pastki 2', 'exercises' => [
                    ['Rumincha o\'lik tortish', 4, '8-10', 120],
                    ['Bolgarcha split-squat', 3, '10 har oyoqqa', 90],
                    ['Glute bridge, bir oyoqda', 3, '12 har oyoqqa', 60],
                    ['Boldir ko\'tarish', 3, '15', 60],
                ]],
                ['name' => 'Yuqori 2', 'exercises' => [
                    ['O\'tirib blok tortish (seated row)', 3, '10-12', 90],
                    ['O\'tirib yelka jimi, gantel bilan', 3, '10-12', 60],
                    ['Triseps uchun blok', 3, '12-15', 60],
                    ['Velosiped (press uchun)', 3, '20', 60],
                ]],
            ],
        ],
        'f_home_3' => [
            'gender' => 'female',
            'name' => 'Uyda, 3 kun',
            'level' => "Boshlang'ich",
            'recommended' => false,
            'weekdays' => [1, 3, 5],
            'about' => 'Zalsiz, uyda o\'z vazn bilan yoki ikkita gantel bilan.',
            'days' => [
                ['name' => 'Uy mashqi', 'exercises' => [
                    ['O\'tirib turish (squat)', 4, '15-20', 60],
                    ['Glute bridge (dumba ko\'prigi)', 4, '15-20', 60],
                    ['Tizzada otjimaniya', 3, '8-12', 60],
                    ['Hujum (lunge)', 3, '12 har oyoqqa', 60],
                    ['Plank', 3, '30-45 s', 60],
                ]],
            ],
        ],
        'f_stretch_2' => [
            'gender' => 'female',
            'name' => "Cho'zilish va harakatchanlik, 2 kun",
            'level' => 'Hamma uchun',
            'recommended' => false,
            'weekdays' => [3, 7],
            'about' => 'Yengil kunlar uchun: egiluvchanlik, qomat va bel og\'rig\'ining oldini olish.',
            'days' => [
                ['name' => 'Cho\'zilish', 'exercises' => [
                    ['Mushuk-sigir (cat-cow)', 2, '10', 30],
                    ['Bola pozasi', 2, '45 s', 30],
                    ['Son orqa mushagini cho\'zish', 2, '30 s har oyoqqa', 30],
                    ['Kestirib (hip flexor) cho\'zish', 2, '30 s har oyoqqa', 30],
                    ['Ko\'krak va yelkani ochish', 2, '30 s', 30],
                ]],
            ],
        ],
    ];

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function forGender(?string $gender): array
    {
        if (! in_array($gender, ['male', 'female'], true)) {
            return [];
        }

        return array_filter(self::PROGRAMS, fn ($p) => $p['gender'] === $gender);
    }

    /**
     * Faqat shu jins uchun bo'lsa dasturni qaytaradi.
     *
     * @return array<string, mixed>|null
     */
    public static function find(string $key, ?string $gender): ?array
    {
        $program = self::PROGRAMS[$key] ?? null;

        return $program && $program['gender'] === $gender ? $program : null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function card(string $key, array $p): array
    {
        return [
            'key' => $key,
            'name' => $p['name'],
            'level' => $p['level'],
            'recommended' => $p['recommended'],
            'weekdays' => $p['weekdays'],
            'about' => $p['about'],
            'days' => array_map(fn ($d) => [
                'name' => $d['name'],
                'exercises' => array_map(fn ($e) => ['name' => $e[0], 'sets' => $e[1], 'reps' => $e[2], 'rest' => $e[3]], $d['exercises']),
            ], $p['days']),
        ];
    }
}

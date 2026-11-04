<?php

namespace App\Modules\Core\Demo;

use App\Models\User;
use App\Modules\Core\Area;
use App\Modules\Geo\Region;
use App\Modules\Ilm\Book;
use App\Modules\Planner\Task;
use App\Modules\Routine\HabitDefaults;
use Carbon\CarbonImmutable;

/**
 * Umumiy demo hisob. Hamma mehmonlar shu bitta hisobni ko'radi; yozish
 * BlockDemoWrites orqali yopilgan, shuning uchun ma'lumot o'zgarmaydi.
 */
class DemoAccount
{
    private const TASKS = [
        ['07:00', '07:30', 'Ertalabki mashq', true],
        ['09:00', '11:00', 'Matematika: 3-bob masalalari', true],
        ['15:00', '16:00', 'Ingliz tili: 20 ta yangi so\'z', false],
        [null, null, 'Kurs ishi uchun manbalar yig\'ish', false],
    ];

    private const TOMORROW = [
        ['10:00', '12:00', 'Fizika laboratoriyasi'],
        [null, null, 'Kitob: 30 bet o\'qish'],
    ];

    public static function user(): User
    {
        // is_demo fillable emas: ro'yxatdan o'tish formasi orqali o'rnatib bo'lmaydi.
        $user = User::where('is_demo', true)->first() ?? tap(new User, fn (User $u) => $u->forceFill([
            'is_demo' => true,
            'first_name' => 'Demo',
            'last_name' => 'Talaba',
            'gender' => 'male',
            'region_id' => Region::where('name', 'Toshkent shahri')->value('id'),
            'timezone' => 'Asia/Tashkent',
            'onboarding_step' => User::ONBOARDING_DONE,
        ])->save());
        HabitDefaults::ensure($user);
        self::fill($user);

        return $user;
    }

    /** Har kuni birinchi kirishda bugungi va ertangi namuna vazifalar qo'shiladi. */
    private static function fill(User $user): void
    {
        $today = CarbonImmutable::now($user->timezone)->startOfDay();
        $tasks = Task::withoutGlobalScope('owner')->where('user_id', $user->id);

        if (! (clone $tasks)->whereDate('date', $today->toDateString())->exists()) {
            foreach (self::TASKS as $i => [$start, $end, $title, $done]) {
                Task::create([
                    'user_id' => $user->id, 'title' => $title, 'date' => $today->toDateString(),
                    'start_time' => $start, 'end_time' => $end, 'position' => $i + 1,
                    'done_at' => $done ? $today->setTimeFromTimeString($end ?? '12:00')->utc() : null,
                ]);
            }
            foreach (self::TOMORROW as $i => [$start, $end, $title]) {
                Task::create([
                    'user_id' => $user->id, 'title' => $title, 'date' => $today->addDay()->toDateString(),
                    'start_time' => $start, 'end_time' => $end, 'position' => $i + 1,
                ]);
            }
        }

        if (! Book::withoutGlobalScope('owner')->where('user_id', $user->id)->exists()) {
            $books = [
                ['title' => 'Atomic Habits', 'author' => 'James Clear', 'total_pages' => 320, 'current_page' => 140, 'status' => 'reading', 'area' => Area::STUDY],
                ['title' => 'Designing Data-Intensive Applications', 'author' => 'Martin Kleppmann', 'total_pages' => 560, 'current_page' => 0, 'status' => 'planned', 'area' => Area::STUDY],
            ];
            foreach ($books as $i => $book) {
                Book::create([
                    'user_id' => $user->id, 'area_id' => Area::idFor($book['area']), 'position' => $i + 1,
                    'started_on' => $book['status'] === 'reading' ? $today->subDays(10)->toDateString() : null,
                ] + collect($book)->except('area')->all());
            }
        }
    }
}

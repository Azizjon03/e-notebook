<?php

namespace App\Modules\Ilm;

use App\Models\User;
use App\Modules\Core\Area;
use App\Modules\Core\Points;
use Carbon\CarbonImmutable;

/**
 * Kitobda oldinga siljishni yozadi: o'qish logi, joriy sahifa, holat va ilm uchun kunlik ball.
 */
class ReadingProgress
{
    public function __construct(private readonly Points $points) {}

    /**
     * @return int o'qilgan betlar soni (oldinga siljish bo'lmasa 0)
     */
    public function record(Book $book, User $user, int $toPage, ?string $chapter = null): int
    {
        if ($book->total_pages) {
            $toPage = min($toPage, $book->total_pages);
        }

        if ($toPage <= $book->current_page) {
            return 0;
        }

        $today = CarbonImmutable::now($user->timezone)->toDateString();
        $from = $book->current_page;

        $book->logs()->create([
            'date' => $today,
            'from_page' => $from,
            'to_page' => $toPage,
            'chapter' => $chapter,
        ]);

        $finished = $book->total_pages && $toPage >= $book->total_pages;
        $book->update([
            'current_page' => $toPage,
            'status' => $finished ? 'finished' : 'reading',
            'started_on' => $book->started_on ?? $today,
            'finished_on' => $finished ? $today : null,
        ]);

        // Ilm kitobidan kunlik o'qish uchun kuniga bir marta ball.
        if ($book->area?->key === Area::ILM) {
            $this->points->award($user, $user, 'ilm_reading:'.$today, (int) config('points.ilm_reading'));
        }

        return $toPage - $from;
    }
}

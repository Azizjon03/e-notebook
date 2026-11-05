<?php

namespace App\Modules\Study;

use App\Modules\Core\Points;
use App\Modules\Ilm\Book;
use App\Modules\Ilm\ReadingProgress;

/**
 * Sessiyani yopadi: daqiqa va ball hisoblanadi; kitob bilan bo'lsa, PDF da oxirgi ochilgan bet
 * bo'yicha o'qilgan betlar yoziladi.
 */
class SessionFinisher
{
    /** Unutilgan taymer shu daqiqadan ko'p hisoblanmaydi. */
    public const MAX_MINUTES = 240;

    public function __construct(
        private readonly Points $points,
        private readonly ReadingProgress $progress,
    ) {}

    public function finish(StudySession $session): void
    {
        $minutes = min(self::MAX_MINUTES, (int) floor($session->started_at->diffInSeconds(now()) / 60));
        $data = ['ended_at' => now(), 'minutes' => $minutes];

        $book = $session->book_id ? Book::withoutGlobalScope('owner')->find($session->book_id) : null;
        if ($book) {
            $end = $book->last_viewed_page ?? $session->start_page ?? 0;
            $data['end_page'] = $end;
            $data['pages_read'] = max(0, $end - (int) $session->start_page);
            $this->progress->record($book, $session->user, $end);
        }

        $session->update($data);

        $this->points->award($session->user, $session, 'session', StudySession::pointsFor($minutes));
    }
}

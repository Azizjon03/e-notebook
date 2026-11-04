<?php

namespace App\Modules\Ilm;

use App\Modules\Core\Area;
use App\Modules\Core\Points;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BookController
{
    public function index(Request $request): Response
    {
        $tz = $request->user()->timezone;
        $weekAgo = CarbonImmutable::now($tz)->subDays(6)->toDateString();

        $books = Book::with(['area', 'logs' => fn ($q) => $q->latest('id')->limit(1)])
            ->orderBy('position')->orderBy('id')->get();

        $pagesWeek = ReadingLog::whereIn('book_id', $books->pluck('id'))
            ->whereDate('date', '>=', $weekAgo)->get()->sum(fn (ReadingLog $l) => $l->pages());

        return Inertia::render('Books', [
            'books' => $books->map->toCard()->values(),
            'pagesWeek' => $pagesWeek,
            'areas' => Area::whereIn('key', [Area::ILM, Area::STUDY])->orderBy('sort')->get(['key', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        unset($data['area']);
        Book::create($data + [
            'area_id' => Area::idFor($request->input('area')),
            'position' => (int) Book::max('position') + 1,
        ]);

        return back();
    }

    public function update(Request $request, Book $book): RedirectResponse
    {
        $data = $this->validated($request, $book);
        unset($data['area']);
        $book->update($data + ['area_id' => Area::idFor($request->input('area'))]);

        return back();
    }

    public function destroy(Book $book): RedirectResponse
    {
        $book->delete();

        return back();
    }

    public function move(Request $request, Book $book): RedirectResponse
    {
        $direction = $request->validate(['direction' => ['required', Rule::in(['up', 'down'])]])['direction'];

        $neighbour = Book::where('status', $book->status)
            ->where('position', $direction === 'up' ? '<' : '>', $book->position)
            ->orderBy('position', $direction === 'up' ? 'desc' : 'asc')
            ->first();

        if ($neighbour) {
            DB::transaction(function () use ($book, $neighbour) {
                [$a, $b] = [$book->position, $neighbour->position];
                $book->update(['position' => $b]);
                $neighbour->update(['position' => $a]);
            });
        }

        return back();
    }

    /**
     * O'qilgan sahifani yozadi: kitob o'qilayotganga o'tadi, oxiriga yetsa tugatilgan bo'ladi.
     */
    public function log(Request $request, Book $book, Points $points): RedirectResponse
    {
        $max = $book->total_pages ?? 100000;
        $data = $request->validate([
            'to_page' => ['required', 'integer', 'min:'.($book->current_page + 1), 'max:'.$max],
            'chapter' => ['nullable', 'string', 'max:150'],
        ], [
            'to_page.min' => 'Sahifa hozirgi joydan ('.$book->current_page.') katta bo\'lsin.',
            'to_page.max' => 'Kitobda '.$max.' sahifa bor.',
        ]);

        $user = $request->user();
        $today = CarbonImmutable::now($user->timezone)->toDateString();

        $book->logs()->create([
            'date' => $today,
            'from_page' => $book->current_page,
            'to_page' => $data['to_page'],
            'chapter' => $data['chapter'] ?? null,
        ]);

        $finished = $book->total_pages && $data['to_page'] >= $book->total_pages;
        $book->update([
            'current_page' => $data['to_page'],
            'status' => $finished ? 'finished' : 'reading',
            'started_on' => $book->started_on ?? $today,
            'finished_on' => $finished ? $today : null,
        ]);

        // Ilm kitobidan kunlik o'qish uchun kuniga bir marta ball.
        if ($book->area?->key === Area::ILM) {
            $points->award($user, $user, 'ilm_reading:'.$today, (int) config('points.ilm_reading'));
        }

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Book $book = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'author' => ['nullable', 'string', 'max:150'],
            'stage' => ['nullable', 'string', 'max:100'],
            'area' => ['required', Rule::in([Area::ILM, Area::STUDY])],
            'total_pages' => ['nullable', 'integer', 'min:1', 'max:100000', 'gte:'.($book?->current_page ?? 0)],
            'status' => ['sometimes', Rule::in(Book::STATUSES)],
        ], ['title.required' => 'Kitob nomini yozing.']);
    }
}

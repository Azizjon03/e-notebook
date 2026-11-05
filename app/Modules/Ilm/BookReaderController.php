<?php

namespace App\Modules\Ilm;

use App\Modules\Study\SessionFinisher;
use App\Modules\Study\StudySession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Kitob PDF fayli: yuklash, ilova ichida o'qish, oxirgi betni saqlash va vaqtli o'qish sessiyasi.
 */
class BookReaderController
{
    public const MAX_MB = 50;

    public function upload(Request $request, Book $book): RedirectResponse
    {
        $request->validate([
            'pdf' => ['required', 'file', 'mimetypes:application/pdf', 'max:'.(self::MAX_MB * 1024)],
        ], [
            'pdf.required' => __('PDF faylni tanlang.'),
            'pdf.uploaded' => __('Fayl :mb MB dan katta bo\'lmasin.', ['mb' => self::MAX_MB]),
            'pdf.mimetypes' => __('Faqat PDF fayl yuklash mumkin.'),
            'pdf.max' => __('Fayl :mb MB dan katta bo\'lmasin.', ['mb' => self::MAX_MB]),
        ]);

        $file = $request->file('pdf');
        $path = $file->storeAs('books/'.$book->user_id, Str::uuid().'.pdf', 'local');

        $old = $book->pdf_path;
        $book->forceFill([
            'pdf_path' => $path,
            'pdf_name' => Str::limit($file->getClientOriginalName(), 190, ''),
            'pdf_size' => $file->getSize(),
            'last_viewed_page' => null,
        ])->save();

        if ($old) {
            Storage::disk('local')->delete($old);
        }

        return back();
    }

    public function file(Book $book): BinaryFileResponse
    {
        abort_unless($book->hasPdf() && Storage::disk('local')->exists($book->pdf_path), 404);

        return response()->file(Storage::disk('local')->path($book->pdf_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="book.pdf"',
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function removePdf(Book $book): RedirectResponse
    {
        $book->deletePdf();

        return back();
    }

    public function show(Book $book): Response|RedirectResponse
    {
        if (! $book->hasPdf()) {
            return redirect()->route('books');
        }

        $running = StudySession::whereNull('ended_at')->latest('started_at')->first();

        return Inertia::render('Reader', [
            'book' => $book->toCard(),
            'pdfUrl' => route('books.pdf', $book),
            'startPage' => max(1, $book->last_viewed_page ?? $book->current_page),
            'running' => $running ? [
                'id' => $running->id,
                'book_id' => $running->book_id,
                'title' => $running->title,
                'planned_minutes' => $running->planned_minutes,
                'started_at' => $running->started_at->toIso8601String(),
                'start_page' => $running->start_page,
            ] : null,
        ]);
    }

    /**
     * O'quvchida ochilgan betni saqlaydi (har bet almashganda, kechiktirib).
     */
    public function progress(Request $request, Book $book): HttpResponse
    {
        $data = $request->validate([
            'page' => ['required', 'integer', 'min:1', 'max:100000'],
            'total' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);

        // Betlar soni kiritilmagan bo'lsa, PDF dagi betlar soni olinadi.
        if (! $book->total_pages && ! empty($data['total'])) {
            $book->total_pages = max($data['total'], $book->current_page);
        }
        $book->last_viewed_page = $book->total_pages ? min($data['page'], $book->total_pages) : $data['page'];
        $book->save();

        return response()->noContent();
    }

    public function startSession(Request $request, Book $book, SessionFinisher $finisher): RedirectResponse
    {
        $data = $request->validate(['planned_minutes' => ['required', 'integer', 'between:5,180']]);

        StudySession::whereNull('ended_at')->get()->each(fn (StudySession $s) => $finisher->finish($s));

        StudySession::create([
            'area_id' => $book->area_id,
            'book_id' => $book->id,
            'title' => Str::limit($book->title, 150, ''),
            'planned_minutes' => $data['planned_minutes'],
            'start_page' => max(1, $book->last_viewed_page ?? $book->current_page),
            'started_at' => now(),
        ]);

        return back();
    }
}

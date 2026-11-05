<?php

use App\Models\User;
use App\Modules\Core\Reminders\ReminderPlanner;
use App\Modules\Ilm\Book;
use App\Modules\Study\StudySession;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    Carbon::setTestNow(Carbon::parse('2026-11-05 10:00:00', 'UTC'));
    $this->user = User::factory()->create(['timezone' => 'Asia/Tashkent']);
    $this->actingAs($this->user);
    $this->post('/books', ['title' => 'DDIA', 'area' => 'study']);
    $this->book = Book::latest('id')->first();
});

function fakePdf(string $name = 'ddia.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF\n");
}

it('uploads a pdf, serves it to the owner only and replaces it', function () {
    $this->post("/books/{$this->book->id}/pdf", ['pdf' => fakePdf()])->assertRedirect()->assertSessionHasNoErrors();
    $book = $this->book->refresh();
    $first = $book->pdf_path;

    expect($book->hasPdf())->toBeTrue()->and($book->pdf_name)->toBe('ddia.pdf');
    Storage::disk('local')->assertExists($first);

    $this->get("/books/{$book->id}/pdf")->assertOk()->assertHeader('Content-Type', 'application/pdf');

    $this->post("/books/{$book->id}/pdf", ['pdf' => fakePdf('v2.pdf')]);
    Storage::disk('local')->assertMissing($first);
    Storage::disk('local')->assertExists($book->refresh()->pdf_path);

    $this->actingAs(User::factory()->create());
    $this->get("/books/{$book->id}/pdf")->assertNotFound();
    $this->get("/books/{$book->id}/read")->assertNotFound();
});

it('rejects files that are not pdf', function () {
    $this->post("/books/{$this->book->id}/pdf", ['pdf' => UploadedFile::fake()->createWithContent('notes.txt', 'hello')])
        ->assertSessionHasErrors('pdf');
    expect($this->book->refresh()->hasPdf())->toBeFalse();
});

it('removes the pdf file with the book', function () {
    $this->post("/books/{$this->book->id}/pdf", ['pdf' => fakePdf()]);
    $path = $this->book->refresh()->pdf_path;

    $this->delete("/books/{$this->book->id}");
    Storage::disk('local')->assertMissing($path);
});

it('opens the reader at the last viewed page and fills total pages from the pdf', function () {
    $this->post("/books/{$this->book->id}/pdf", ['pdf' => fakePdf()]);

    $this->put("/books/{$this->book->id}/progress", ['page' => 12, 'total' => 300])->assertNoContent();
    expect($this->book->refresh())->last_viewed_page->toBe(12)->total_pages->toBe(300);

    $this->get("/books/{$this->book->id}/read")->assertInertia(fn (Assert $page) => $page
        ->component('Reader')
        ->where('startPage', 12)
        ->where('book.has_pdf', true)
        ->where('running', null)
    );
});

it('counts pages read in a timed book session', function () {
    $this->post("/books/{$this->book->id}/pdf", ['pdf' => fakePdf()]);
    $this->put("/books/{$this->book->id}/progress", ['page' => 10, 'total' => 300]);

    $this->post("/books/{$this->book->id}/session", ['planned_minutes' => 25])->assertRedirect();
    $session = StudySession::latest('id')->first();
    expect($session->book_id)->toBe($this->book->id)->and($session->start_page)->toBe(10);

    Carbon::setTestNow(now()->addMinutes(26));
    $this->put("/books/{$this->book->id}/progress", ['page' => 24]);
    $this->post("/study/{$session->id}/stop")->assertRedirect();

    $session->refresh();
    $book = $this->book->refresh();
    expect($session->pages_read)->toBe(14)
        ->and($session->end_page)->toBe(24)
        ->and($session->minutes)->toBe(26)
        ->and($book->current_page)->toBe(24)
        ->and($book->status)->toBe('reading');

    $this->get('/books')->assertInertia(fn (Assert $page) => $page
        ->where('pagesToday', 24)
        ->where('books.0.pages_today', 24)
    );
    $this->get('/study')->assertInertia(fn (Assert $page) => $page->where('today.0.pages', 14));
});

it('does not move the book back when the reader goes to an earlier page', function () {
    $this->post("/books/{$this->book->id}/log", ['to_page' => 50]);
    $this->post("/books/{$this->book->id}/pdf", ['pdf' => fakePdf()]);
    $this->post("/books/{$this->book->id}/session", ['planned_minutes' => 15]);
    $this->put("/books/{$this->book->id}/progress", ['page' => 5]);

    $session = StudySession::latest('id')->first();
    $this->post("/study/{$session->id}/stop");

    expect($this->book->refresh()->current_page)->toBe(50)
        ->and($session->refresh()->pages_read)->toBe(0);
});

it('sends a telegram reminder when the study timer ends', function () {
    $this->user->update(['telegram_id' => 777, 'onboarding_step' => User::ONBOARDING_DONE]);
    $this->post('/study/start', ['area' => 'study', 'planned_minutes' => 25, 'title' => 'DDIA 5-bob']);

    $planner = app(ReminderPlanner::class);
    $refs = fn () => collect($planner->due($this->user->refresh(), CarbonImmutable::now('UTC')))->pluck('text', 'ref');

    expect($refs()->keys()->filter(fn ($r) => str_starts_with($r, 'study:')))->toBeEmpty();

    Carbon::setTestNow(now()->addMinutes(25));
    $due = $refs()->filter(fn ($text, $ref) => str_starts_with($ref, 'study:'));
    expect($due)->toHaveCount(1)->and($due->first())->toContain('DDIA 5-bob');
});

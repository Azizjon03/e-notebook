<?php

use App\Models\User;
use App\Modules\Core\Points;
use App\Modules\Ilm\Book;
use App\Modules\Ilm\ReadingLog;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-04 14:00:00', 'UTC'));
    $this->user = User::factory()->create(['timezone' => 'Asia/Tashkent']);
    $this->actingAs($this->user);
});

function addBook(array $overrides = []): Book
{
    test()->post('/books', array_merge(['title' => 'Arbain', 'author' => 'Imom Navaviy', 'area' => 'ilm', 'total_pages' => 120], $overrides))
        ->assertRedirect();

    return Book::latest('id')->first();
}

it('adds books to the queue in order', function () {
    $a = addBook();
    $b = addBook(['title' => 'Designing Data-Intensive Applications', 'area' => 'study', 'total_pages' => 560]);

    expect($a->status)->toBe('planned')
        ->and($b->position)->toBeGreaterThan($a->position)
        ->and($b->area->key)->toBe('study');

    $this->get('/books')->assertInertia(fn (Assert $page) => $page
        ->component('Books')
        ->has('books', 2)
        ->where('books.0.title', 'Arbain')
    );
});

it('validates the title and area', function () {
    $this->post('/books', ['title' => '', 'area' => 'ilm'])->assertSessionHasErrors('title');
    $this->post('/books', ['title' => 'X', 'area' => 'sport'])->assertSessionHasErrors('area');
});

it('logs pages, starts reading and finishes at the last page', function () {
    $book = addBook();

    $this->post("/books/{$book->id}/log", ['to_page' => 40, 'chapter' => '1-12 hadislar']);
    $book->refresh();
    expect($book->status)->toBe('reading')
        ->and($book->current_page)->toBe(40)
        ->and($book->started_on->toDateString())->toBe('2026-11-04')
        ->and($book->percent())->toBe(33);

    $this->post("/books/{$book->id}/log", ['to_page' => 120]);
    $book->refresh();
    expect($book->status)->toBe('finished')
        ->and($book->finished_on->toDateString())->toBe('2026-11-04')
        ->and(ReadingLog::where('book_id', $book->id)->orderBy('id')->get(['from_page', 'to_page'])->toArray())
        ->toBe([['from_page' => 0, 'to_page' => 40], ['from_page' => 40, 'to_page' => 120]]);
});

it('rejects going backwards or past the last page', function () {
    $book = addBook();
    $this->post("/books/{$book->id}/log", ['to_page' => 50]);

    $this->post("/books/{$book->id}/log", ['to_page' => 30])->assertSessionHasErrors('to_page');
    $this->post("/books/{$book->id}/log", ['to_page' => 121])->assertSessionHasErrors('to_page');
});

it('gives ilm reading points once a day, none for study books', function () {
    $ilm = addBook();
    $study = addBook(['title' => 'DDIA', 'area' => 'study']);

    $this->post("/books/{$ilm->id}/log", ['to_page' => 10]);
    $this->post("/books/{$ilm->id}/log", ['to_page' => 20]);
    $this->post("/books/{$study->id}/log", ['to_page' => 30]);
    expect(app(Points::class)->total($this->user))->toBe(5);

    Carbon::setTestNow(now()->addDay());
    $this->post("/books/{$ilm->id}/log", ['to_page' => 30]);
    expect(app(Points::class)->total($this->user))->toBe(10);
});

it('reorders books within the queue', function () {
    $a = addBook(['title' => 'A']);
    $b = addBook(['title' => 'B']);

    $this->post("/books/{$b->id}/move", ['direction' => 'up']);

    $this->get('/books')->assertInertia(fn (Assert $page) => $page->where('books.0.title', 'B')->where('books.1.title', 'A'));
});

it('keeps books private', function () {
    $book = addBook();
    $this->actingAs(User::factory()->create());

    $this->get('/books')->assertInertia(fn (Assert $page) => $page->has('books', 0));
    $this->post("/books/{$book->id}/log", ['to_page' => 10])->assertNotFound();
    $this->delete("/books/{$book->id}")->assertNotFound();
});

<?php

use App\Models\User;
use App\Modules\Planner\Task;

beforeEach(fn () => $this->seed());

it('shows the demo to a guest without registration', function () {
    $this->post('/demo')->assertRedirect(route('today'));

    $demo = User::firstWhere('is_demo', true);
    $this->assertAuthenticatedAs($demo);
    $this->get('/')->assertOk();
    $this->get('/books')->assertOk();
    expect(Task::withoutGlobalScope('owner')->where('user_id', $demo->id)->count())->toBeGreaterThan(0);

    // Ikkinchi mehmon ham shu hisobni ko'radi, vazifalar ko'paymaydi.
    $count = Task::withoutGlobalScope('owner')->count();
    $this->post('/demo');
    expect(Task::withoutGlobalScope('owner')->count())->toBe($count);
});

it('sends demo visitors to registration when they try to change something', function () {
    $this->post('/demo');
    $count = Task::withoutGlobalScope('owner')->count();

    $this->post('/tasks', ['title' => 'Yangi', 'date' => now()->toDateString()])
        ->assertRedirect(route('register'))
        ->assertSessionHas('error');

    expect(Task::withoutGlobalScope('owner')->count())->toBe($count);
    $this->assertGuest();
});

it('keeps the demo language in the visitor session only', function () {
    $this->post('/demo');
    $this->post('/locale', ['locale' => 'ru'])->assertRedirect();

    expect(User::firstWhere('is_demo', true)->locale)->toBe('uz');
    $this->get('/')->assertInertia(fn ($page) => $page->where('locale', 'ru'));
});

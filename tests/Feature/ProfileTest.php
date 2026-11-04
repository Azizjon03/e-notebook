<?php

use App\Models\User;
use App\Modules\Planner\Task;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->seed());

it('shows the profile with own totals only', function () {
    $user = User::factory()->create(['phone' => '+998901234567']);
    $other = User::factory()->create();
    Task::factory()->for($user)->create(['done_at' => now()]);
    Task::factory()->for($other)->create(['done_at' => now()]);

    $this->actingAs($user)->get('/profile')->assertInertia(fn (Assert $page) => $page
        ->component('Profile')
        ->where('profile.phone', '+998901234567')
        ->where('totals.tasks_done', 1)
        ->has('level.level'));
});

it('updates the name but keeps a gender that was already chosen', function () {
    $user = User::factory()->create(['gender' => 'female']);

    $this->actingAs($user)->put('/profile', ['first_name' => 'Laylo', 'last_name' => '', 'gender' => 'male'])
        ->assertSessionHasNoErrors();

    expect($user->fresh())->first_name->toBe('Laylo')->gender->toBe('female');
});

it('lets the demo visitor see the profile but not change it', function () {
    $this->post('/demo');
    $this->get('/profile')->assertOk();
    $this->put('/profile', ['first_name' => 'Hack'])->assertRedirect(route('register'));

    expect(User::firstWhere('is_demo', true)->first_name)->toBe('Demo');
});

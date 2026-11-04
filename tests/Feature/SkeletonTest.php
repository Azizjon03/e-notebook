<?php

use Inertia\Testing\AssertableInertia as Assert;

it('opens the Today page through Inertia', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Today')
            ->has('date')
        );
});

it('answers the health check', function () {
    $this->get('/up')->assertOk();
});

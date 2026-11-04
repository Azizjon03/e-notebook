<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Today', [
    'date' => now()->toDateString(),
]))->name('today');

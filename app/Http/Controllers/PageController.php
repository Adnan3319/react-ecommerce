<?php
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\PageController;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/about', [PageController::class, 'about']);
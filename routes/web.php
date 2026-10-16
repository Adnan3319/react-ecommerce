<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', HomeController::class)->name('home');
Route::get('/products/{slug}', fn (string $slug) => Inertia::render('Products/Show', ['slug' => $slug]))->name('products.show');
Route::inertia('/search', 'Search')->name('search');
Route::inertia('/cart', 'Cart')->name('cart');
Route::inertia('/about', 'About')->name('about');
Route::inertia('/contact', 'Contact')->name('contact');

Route::prefix('admin')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/signin', [AdminAuthController::class, 'create'])->name('login');
        Route::post('/signin', [AdminAuthController::class, 'store']);
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'destroy']);
        Route::get('/services', fn () => Inertia::render('Admin/Services/Index'));
        Route::get('/portfolio', fn () => Inertia::render('Admin/Portfolio/Index'));
        Route::get('/team', fn () => Inertia::render('Admin/Team/Index'));
        Route::get('/clients', fn () => Inertia::render('Admin/Clients/Index'));
        Route::get('/blog', fn () => Inertia::render('Admin/Blog/Index'));
        Route::get('/career', fn () => Inertia::render('Admin/Career/Index'));
        Route::get('/contact', fn () => Inertia::render('Admin/Contact/Index'));
        Route::get('/about', fn () => Inertia::render('Admin/About/Index'));
        

        Route::get('/dashboard', fn () => Inertia::render('Admin/Dashboard/Ecommerce'));

        Route::get('/profile', fn () => Inertia::render('Admin/UserProfiles'));
        Route::get('/blank', fn () => Inertia::render('Admin/OtherPage/Blank'));


        Route::get('/form-elements', fn () => Inertia::render('Admin/Forms/FormElements'));
        Route::get('/basic-tables', fn () => Inertia::render('Admin/Tables/BasicTables'));

        Route::get('/badge', fn () => Inertia::render('Admin/UiElements/Badges'));
        Route::get('/buttons', fn () => Inertia::render('Admin/UiElements/Buttons'));

    });
});
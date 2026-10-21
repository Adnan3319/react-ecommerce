<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerBlockController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CategoryController as StorefrontCategoryController;
use App\Http\Controllers\Storefront\OrderController;
use App\Http\Controllers\Storefront\ProductOptionsController;
use App\Http\Controllers\Storefront\ProductController as StorefrontProductController;
use App\Http\Controllers\Storefront\SearchController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', HomeController::class)->name('home');
Route::get('/products/{slug}', [StorefrontProductController::class, 'show'])->name('products.show');
Route::get('/products/{slug}/options', [ProductOptionsController::class, 'show'])->middleware('throttle:product-options')->name('products.options');
Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:orders')->name('orders.store');
Route::get('/orders/{token}/thank-you', [OrderController::class, 'show'])->name('orders.success');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/categories/{slug}', [StorefrontCategoryController::class, 'show'])->name('categories.show');
Route::get('/cart', [CartController::class, 'show'])->name('cart');
Route::middleware('throttle:cart')->prefix('cart/items')->name('cart.items.')->group(function () {
    Route::post('/', [CartController::class, 'store'])->name('store');
    Route::patch('/{variantId}', [CartController::class, 'update'])->whereNumber('variantId')->name('update');
    Route::delete('/{variantId}', [CartController::class, 'destroy'])->whereNumber('variantId')->name('destroy');
});
Route::inertia('/about', 'About')->name('about');
Route::inertia('/contact', 'Contact')->name('contact');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/dashboard')->name('index');

    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::resource('sliders', SliderController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('testimonials', TestimonialController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('products', ProductController::class)->except('show');

        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/transition', [AdminOrderController::class, 'transition'])->name('orders.transition');
        Route::post('/orders/{order}/notes', [AdminOrderController::class, 'note'])->name('orders.notes');
        Route::post('/customers/{customer}/block', [CustomerBlockController::class, 'store'])->withTrashed()->name('customers.block');
        Route::delete('/customers/{customer}/block', [CustomerBlockController::class, 'destroy'])->withTrashed()->name('customers.unblock');

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
});

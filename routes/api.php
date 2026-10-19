<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SliderController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\AboutController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\BlogController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\SettingController;


// Protected Auth & XSS Protected API Routes
Route::middleware(['auth:sanctum', \App\Http\Middleware\RejectXss::class])->group(function () {
    Route::post('categories/upload', [CategoryController::class, 'upload']);
    Route::apiResource('categories', CategoryController::class);
    Route::post('sliders/upload', [SliderController::class, 'upload']);
    Route::apiResource('sliders', SliderController::class);
    Route::apiResource('about', AboutController::class);
    Route::apiResource('services', ServiceController::class);
    Route::apiResource('portfolios', PortfolioController::class);
    Route::apiResource('teams', TeamController::class);
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('blogs', BlogController::class);
    Route::apiResource('contacts', ContactController::class);

    Route::get('settings', [SettingController::class, 'index']);
    Route::post('settings', [SettingController::class, 'update']);
});
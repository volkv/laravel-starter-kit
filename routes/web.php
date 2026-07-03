<?php

use Illuminate\Support\Facades\Route;

Route::get('/internal/opcache-clear', [App\Http\Controllers\InternalController::class, 'opcacheClear']);

Route::get('/', [App\Http\Controllers\HomeController::class, 'welcome'])->name('welcome');
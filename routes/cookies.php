<?php



use App\Http\Controllers\CookiePageController;

use Illuminate\Support\Facades\Route;



Route::get('/cookies', CookiePageController::class)->name('cookies');


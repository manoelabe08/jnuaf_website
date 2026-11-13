<?php



use App\Http\Controllers\EventPageController;

use Illuminate\Support\Facades\Route;



Route::get('/evenements/{slug}', [EventPageController::class, 'show'])->where('slug', '[a-z0-9][a-z0-9-]{0,99}')->name('events.show');

Route::post('/evenements/{slug}/inscription', [EventPageController::class, 'store'])->where('slug', '[a-z0-9][a-z0-9-]{0,99}')->middleware('throttle:public-forms')->name('events.register');


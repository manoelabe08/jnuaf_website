<?php



use App\Http\Controllers\CommitteePoleResponseController;

use Illuminate\Support\Facades\Route;



Route::get('/comite/pole/{token}', [CommitteePoleResponseController::class, 'show'])->middleware('throttle:30,1')->name('committee.pole.show');

Route::post('/comite/pole/{token}', [CommitteePoleResponseController::class, 'answer'])->middleware('throttle:10,1')->name('committee.pole.answer');


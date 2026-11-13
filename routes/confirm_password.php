<?php



use App\Http\Controllers\Auth\ConfirmPasswordController;

use Illuminate\Support\Facades\Route;



Route::middleware('auth')->group(function () {

    Route::get('/admin/confirmer-mot-de-passe', [ConfirmPasswordController::class, 'show'])->name('password.confirm');

    Route::post('/admin/confirmer-mot-de-passe', [ConfirmPasswordController::class, 'store'])->middleware('throttle:5,1');

});


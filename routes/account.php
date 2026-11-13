<?php



use App\Http\Controllers\Auth\AccountLoginController;

use Illuminate\Support\Facades\Route;



Route::get('/account', [AccountLoginController::class, 'show'])->name('account.show');

Route::post('/account', [AccountLoginController::class, 'sendLink'])->middleware('throttle:6,1')->name('account.send');

Route::get('/account/connexion/{token}', [AccountLoginController::class, 'passwordForm'])->middleware('throttle:30,1')->name('account.password');

Route::post('/account/connexion/{token}', [AccountLoginController::class, 'login'])->middleware('throttle:10,1')->name('account.login');


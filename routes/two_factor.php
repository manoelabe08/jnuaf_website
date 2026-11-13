<?php

use App\Http\Controllers\Auth\TwoFactorController;
use App\Http\Controllers\TwoFactorSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->prefix('admin/verification')->group(function () {
    Route::get('/', [TwoFactorController::class, 'show'])->name('two-factor.challenge');
    Route::post('/', [TwoFactorController::class, 'verify'])->middleware('throttle:10,1')->name('two-factor.verify');
    Route::post('/renvoyer', [TwoFactorController::class, 'resend'])->middleware('throttle:6,10')->name('two-factor.resend');
    Route::post('/annuler', [TwoFactorController::class, 'cancel'])->name('two-factor.cancel');
});

Route::middleware('auth')->prefix('admin/profil/double-authentification')->group(function () {
    Route::post('/activer', [TwoFactorSettingsController::class, 'enable'])->middleware('throttle:5,1')->name('admin.profile.2fa.enable');
    Route::post('/desactiver', [TwoFactorSettingsController::class, 'disable'])->middleware('throttle:5,1')->name('admin.profile.2fa.disable');
    Route::post('/codes-secours', [TwoFactorSettingsController::class, 'recoveryCodes'])->middleware('throttle:5,1')->name('admin.profile.2fa.codes');
});

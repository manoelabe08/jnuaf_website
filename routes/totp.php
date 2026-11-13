<?php

use App\Http\Controllers\TotpSettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('admin/profil/application-authentification')->group(function () {
    Route::post('/demarrer', [TotpSettingsController::class, 'start'])->middleware('throttle:5,1')->name('admin.profile.totp.start');
    Route::post('/activer', [TotpSettingsController::class, 'enable'])->middleware('throttle:10,1')->name('admin.profile.totp.enable');
    Route::post('/annuler', [TotpSettingsController::class, 'cancel'])->name('admin.profile.totp.cancel');
    Route::post('/desactiver', [TotpSettingsController::class, 'disable'])->middleware('throttle:5,1')->name('admin.profile.totp.disable');
});

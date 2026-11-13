<?php



use App\Http\Controllers\EmailChangeController;

use Illuminate\Support\Facades\Route;



Route::middleware('auth')->group(function () {

    Route::post('/admin/profil/email', [EmailChangeController::class, 'request'])->middleware('throttle:5,10')->name('admin.profile.email');

    Route::post('/admin/profil/email/annuler', [EmailChangeController::class, 'cancel'])->middleware('throttle:10,1')->name('admin.profile.email.cancel');

});



Route::prefix('admin/email')->where(['token' => '[a-f0-9]{64}'])->middleware('throttle:20,1')->group(function () {

    Route::get('/confirmer/{token}', [EmailChangeController::class, 'showConfirm'])->name('email-change.confirm');

    Route::post('/confirmer/{token}', [EmailChangeController::class, 'doConfirm'])->name('email-change.confirm.do');

});



Route::prefix('admin/email')->where(['token' => '[a-f0-9]{64}'])->middleware(['auth', 'admin', 'throttle:20,1'])->group(function () {

    Route::get('/decision/{token}', [EmailChangeController::class, 'showDecision'])->name('email-change.decision');

    Route::post('/decision/{token}', [EmailChangeController::class, 'doDecision'])->name('email-change.decision.do');

});


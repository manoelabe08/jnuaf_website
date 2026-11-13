<?php



use App\Http\Controllers\Admin\SensitiveActionController;

use Illuminate\Support\Facades\Route;



Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {

    Route::get('/confirmation-sensible', [SensitiveActionController::class, 'challenge'])->name('sensitive.challenge');

    Route::post('/confirmation-sensible', [SensitiveActionController::class, 'verify'])->middleware('throttle:10,1')->name('sensitive.verify');

    Route::get('/validations', [SensitiveActionController::class, 'index'])->name('sensitive.index');

    Route::post('/validations/{action}/approuver', [SensitiveActionController::class, 'approve'])->whereNumber('action')->middleware('throttle:10,1')->name('sensitive.approve');

    Route::post('/validations/{action}/refuser', [SensitiveActionController::class, 'reject'])->whereNumber('action')->name('sensitive.reject');
    Route::post('/validations/masse', [SensitiveActionController::class, 'bulk'])->middleware('throttle:10,1')->name('sensitive.bulk');

});


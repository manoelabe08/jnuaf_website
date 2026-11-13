<?php



use App\Http\Controllers\Auth\PasswordChangeController;

use Illuminate\Support\Facades\Route;



Route::middleware('auth')->group(function () {

    Route::get('/admin/changer-mot-de-passe', [PasswordChangeController::class, 'edit'])->name('admin.password.change');

    Route::post('/admin/changer-mot-de-passe', [PasswordChangeController::class, 'update'])->middleware('throttle:10,1')->name('admin.password.change.update');

});


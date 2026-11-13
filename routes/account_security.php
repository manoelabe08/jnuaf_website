<?php



use App\Http\Controllers\Admin\UserController;

use Illuminate\Support\Facades\Route;



Route::middleware(['auth', 'admin'])->group(function () {

    Route::post('/admin/comptes/{user}/reinitialiser-mot-de-passe', [UserController::class, 'resetPassword'])

        ->middleware('throttle:10,1')

        ->whereNumber('user')

        ->name('admin.users.reset-password');

});


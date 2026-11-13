<?php



use App\Http\Controllers\Admin\ProfileController;

use Illuminate\Support\Facades\Route;



Route::middleware('auth')->group(function () {

    Route::get('/admin/profil', [ProfileController::class, 'edit'])->name('admin.profile');

   Route::post('/admin/profil', [ProfileController::class, 'update'])->middleware('throttle:20,1')->name('admin.profile.update');
    Route::post('/admin/profil/photo', [ProfileController::class, 'photo'])->middleware('throttle:10,1')->name('admin.profile.photo');
    Route::delete('/admin/profil/photo', [ProfileController::class, 'removePhoto'])->middleware('throttle:10,1')->name('admin.profile.photo.remove');
    Route::get('/admin/profil/photo/{user}', [ProfileController::class, 'avatar'])->middleware('throttle:240,1')->whereNumber('user')->name('admin.profile.avatar');
    Route::delete('/admin/profil/appareils/{device}', [ProfileController::class, 'forgetDevice'])->middleware('throttle:10,1')->whereNumber('device')->name('admin.profile.device.forget');
    Route::post('/admin/profil/appareils', [ProfileController::class, 'endOtherSessions'])->middleware('throttle:5,1')->name('admin.profile.sessions');
});

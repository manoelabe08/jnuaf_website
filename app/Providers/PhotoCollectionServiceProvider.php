<?php



namespace App\Providers;



use App\Http\Controllers\Admin\CommitteePhotoController;

use App\Http\Controllers\PhotoCollectionController;

use Illuminate\Support\Facades\Route;

use Illuminate\Support\ServiceProvider;



class PhotoCollectionServiceProvider extends ServiceProvider

{

    public function boot(): void

    {

        Route::middleware(['web', 'throttle:20,1'])->group(function () {

            Route::get('/photo/{token}', [PhotoCollectionController::class, 'show'])->where('token', '[A-Za-z0-9]{48}')->name('photo.show');

            Route::post('/photo/{token}', [PhotoCollectionController::class, 'store'])->where('token', '[A-Za-z0-9]{48}')->name('photo.store');

        });

        Route::middleware(['web', 'auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

            Route::get('/comite/{application}/photo', [CommitteePhotoController::class, 'show'])->whereNumber('application')->name('committee.photo');
            Route::post('/comite/{application}/photo/refuser', [CommitteePhotoController::class, 'reject'])->whereNumber('application')->middleware('throttle:10,1')->name('committee.photo.reject');
            Route::get('/inscriptions/{registration}/fichier/{type}', [\App\Http\Controllers\Admin\RegistrationFileController::class, 'show'])->whereNumber('registration')->whereIn('type', ['photo', 'cv'])->name('registrations.file');
            Route::get('/messagerie/suggestions', [\App\Http\Controllers\Admin\MailSuggestController::class, 'index'])->middleware('throttle:120,1')->name('mailbox.suggest');
            Route::post('/cartes/photos/demander', [\App\Http\Controllers\Admin\PhotoRequestController::class, 'store'])->middleware('throttle:10,1')->name('cards.photos.request');

        });

    }

}


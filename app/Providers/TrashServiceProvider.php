<?php



namespace App\Providers;



use App\Http\Controllers\Admin\TrashController;

use App\Services\TrashService;

use Illuminate\Support\Facades\Route;

use Illuminate\Support\ServiceProvider;



class TrashServiceProvider extends ServiceProvider

{

    public function boot(): void

    {

        TrashService::register();

        Route::middleware(['web', 'auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

            Route::get('/corbeille', [TrashController::class, 'index'])->name('trash.index');

            Route::post('/corbeille/{id}/restaurer', [TrashController::class, 'restore'])->whereNumber('id')->middleware('sensitive:2,restore_trash')->name('trash.restore');

            Route::delete('/corbeille/{id}', [TrashController::class, 'purge'])->whereNumber('id')->middleware('sensitive:2,purge_trash')->name('trash.purge');
            Route::post('/comptes/{user}/reactiver', [TrashController::class, 'reactivate'])->middleware('sensitive:1,reactivate_user')->name('users.reactivate');

        });

    }

}



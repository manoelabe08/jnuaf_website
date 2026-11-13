<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\NewsletterSubscriberController;
use App\Http\Controllers\NewsletterPublicController;

Route::prefix('admin/newsletter')->name('admin.newsletter.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [NewsletterController::class, 'index'])->name('index');
    Route::post('/campagnes', [NewsletterController::class, 'store'])->name('store');
    Route::get('/abonnes', [NewsletterSubscriberController::class, 'index'])->name('subscribers');
    Route::get('/abonnes/export', [NewsletterSubscriberController::class, 'export'])->middleware('throttle:6,1')->middleware('sensitive:2,export_subscribers')->name('subscribers.export');
    Route::post('/abonnes', [NewsletterSubscriberController::class, 'store'])->name('subscribers.store');
    Route::put('/abonnes/{subscriber}', [NewsletterSubscriberController::class, 'update'])->name('subscribers.update');
    Route::post('/importer', [NewsletterSubscriberController::class, 'import'])->middleware('throttle:3,1')->name('import');
    Route::get('/campagnes/{campaign}', [NewsletterController::class, 'show'])->name('show');
    Route::put('/campagnes/{campaign}', [NewsletterController::class, 'update'])->name('update');
    Route::get('/campagnes/{campaign}/apercu', [NewsletterController::class, 'preview'])->name('preview');
    Route::post('/campagnes/{campaign}/test', [NewsletterController::class, 'test'])->middleware('throttle:3,1')->name('test');
    Route::post('/campagnes/{campaign}/envoyer', [NewsletterController::class, 'queue'])->middleware('throttle:3,1')->name('queue');
    Route::post('/campagnes/{campaign}/arreter', [NewsletterController::class, 'cancel'])->name('cancel');
    Route::post('/campagnes/{campaign}/forcer', [NewsletterController::class, 'force'])->middleware('throttle:3,1')->middleware('sensitive:2,force_newsletter')->name('force');
    Route::post('/campagnes/{campaign}/envois/{delivery}/priorite', [NewsletterController::class, 'prioritize'])->middleware('throttle:30,1')->name('prioritize');
    Route::post('/campagnes/{campaign}/reprendre', [NewsletterController::class, 'retry'])
->name('retry');

    Route::post('/campagnes/{campaign}/programmer', [NewsletterController::class, 'schedule'])->middleware('throttle:10,1')->name('schedule');

    Route::post('/campagnes/{campaign}/deprogrammer', [NewsletterController::class, 'unschedule'])->name('unschedule');

    Route::get('/campagnes/{campaign}/pieces/{attachment}', [NewsletterController::class, 'file'])->name('file');
    Route::delete('/campagnes/{campaign}/pieces/{attachment}', [NewsletterController::class, 'deleteFile'])->name('file.delete');
});

Route::prefix('newsletter/{token}')->where(['token' => '[a-f0-9]{64}'])
    ->middleware('throttle:60,1')->name('newsletter.')->group(function () {
        Route::get('/', [NewsletterPublicController::class, 'show'])->name('web');
        Route::get('/pieces/{attachment}', [NewsletterPublicController::class, 'file'])->name('file');
        Route::get('/desinscription', [NewsletterPublicController::class, 'unsubscribe'])->name('unsubscribe');
        Route::post('/desinscription', [NewsletterPublicController::class, 'confirm'])->name('unsubscribe.confirm');
    });

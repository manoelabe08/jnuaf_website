<?php



use App\Http\Controllers\Admin\EventRegistrationController;

use Illuminate\Support\Facades\Route;



Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/evenements/{event}/inscrits', [EventRegistrationController::class, 'index'])->whereNumber('event')->name('events.registrations');

    Route::get('/evenements/{event}/inscrits/export', [EventRegistrationController::class, 'export'])->whereNumber('event')->middleware('sensitive:2,export_event')->name('events.registrations.export');

    Route::post('/evenements/{event}/attestations', [EventRegistrationController::class, 'sendAttestations'])->whereNumber('event')->middleware(['throttle:6,1', 'sensitive:2,send_attestations'])->name('events.attestations');

});


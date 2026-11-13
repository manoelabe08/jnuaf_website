<?php

use App\Http\Controllers\MailScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/messagerie/programmes', [MailScheduleController::class, 'index'])->name('admin.mailbox.scheduled');
    Route::post('/admin/messagerie/programmer', [MailScheduleController::class, 'store'])->middleware('throttle:10,1')->name('admin.mailbox.schedule');
    Route::post('/admin/messagerie/programmes/{scheduledMail}/annuler', [MailScheduleController::class, 'cancel'])->whereNumber('scheduledMail')->name('admin.mailbox.unschedule');
});

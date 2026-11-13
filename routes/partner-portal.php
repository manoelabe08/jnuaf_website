<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PartnerPortalController;
use App\Http\Controllers\Admin\PartnerPortalController as AdminPortal;

Route::prefix('admin/documents-partenaires')->name('admin.partner-portal.')
    ->middleware(['auth', 'admin'])->group(function () {
        Route::get('/', [AdminPortal::class, 'index'])->name('index');
        Route::post('/documents', [AdminPortal::class, 'upload'])->name('upload');
        Route::patch('/documents/{document}', [AdminPortal::class, 'toggle'])->name('toggle');
        Route::get('/documents/{document}/apercu', [AdminPortal::class, 'preview'])->name('preview');
        Route::post('/espaces', [AdminPortal::class, 'store'])->name('store');
        Route::get('/espaces/{share}', [AdminPortal::class, 'show'])->name('show');
        Route::put('/espaces/{share}', [AdminPortal::class, 'update'])->name('update');
        Route::post('/espaces/{share}/invitation', [AdminPortal::class, 'invite'])->middleware('throttle:5,1')->name('invite');
        Route::post('/espaces/{share}/revoquer', [AdminPortal::class, 'revoke'])->name('revoke');
    });

Route::prefix('espace-partenaire/{token}')->where(['token' => '[a-f0-9]{64}'])
    ->name('partner-portal.')->middleware('throttle:120,1')->group(function () {
        Route::get('/', [PartnerPortalController::class, 'landing'])->name('landing');
        Route::post('/code', [PartnerPortalController::class, 'requestCode'])->middleware('throttle:10,1')->name('request-code');
        Route::post('/connexion', [PartnerPortalController::class, 'verify'])->middleware('throttle:15,1')->name('verify');
        Route::get('/documents', [PartnerPortalController::class, 'documents'])->name('documents');
        Route::get('/documents/{document}/{mode}', [PartnerPortalController::class, 'file'])
            ->whereNumber('document')->whereIn('mode', ['lire', 'telecharger'])->name('file');
        Route::post('/deconnexion', [PartnerPortalController::class, 'logout'])->name('logout');
    });

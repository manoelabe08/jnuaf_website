<?php



use Illuminate\Support\Facades\Route;



Route::get('/partenaires/logo/{logo}', [\App\Http\Controllers\SponsorLogoController::class, 'show'])->name('sponsors.logo');


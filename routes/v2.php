<?php



use App\Http\Controllers\HomeController;

use App\Http\Controllers\V2Controller;

use App\Http\Controllers\V2PageController;

use Illuminate\Support\Facades\Route;



Route::name('v2.')->group(function () {

    Route::get('/editions', fn () => view('v2.editions'))->middleware('page:editions')->name('editions');

    Route::get('/inscription', [V2PageController::class, 'register'])->middleware('page:register')->name('register');

    Route::get('/intervenantes', [V2PageController::class, 'speakers'])->middleware('page:speakers')->name('speakers');

    Route::get('/comite', fn () => view('v2.committee'))->middleware('page:committee')->name('committee');

    Route::get('/partenaires', fn () => view('v2.partners'))->middleware('page:partners')->name('partners');

    Route::get('/a-propos', fn () => view('v2.about'))->middleware('page:about')->name('about');

    Route::get('/faq', fn () => view('v2.faq'))->middleware('page:faq')->name('faq');

    Route::get('/legal', fn () => view('v2.legal'))->middleware('page:legal')->name('legal');

    Route::get('/equipe', [\App\Http\Controllers\TeamPageController::class, 'index'])->middleware('page:team')->name('team');

    Route::get('/equipe/photo/{application}/{v}', [\App\Http\Controllers\TeamPageController::class, 'photo'])->whereNumber('application')->where('v', '[a-f0-9]{8}')->middleware('page:team')->name('team.photo');




    Route::middleware(['auth', 'admin'])->group(function () {

        Route::get('/nouvel-accueil', [V2Controller::class, 'home'])->name('home');

        Route::get('/old_index', [HomeController::class, 'old'])->name('old_index');

        Route::get('/composants', [V2Controller::class, 'components'])->name('components');

    });

});




/* ---------- Pages v2 (Issues du découpage) ---------- */

use App\Http\Controllers\Public\PageController;



Route::get('/a-propos', [PageController::class, 'about'])->name('v2.about');

Route::get('/programmes', [PageController::class, 'programs'])->name('v2.programs');

Route::get('/gouvernance', [PageController::class, 'governance'])->name('v2.governance');

Route::get('/faq', [PageController::class, 'faq'])->name('v2.faq');


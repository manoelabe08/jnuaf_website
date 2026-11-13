<?php



use App\Http\Controllers\BlogController;

use Illuminate\Support\Facades\Route;



Route::get('/actualites', [BlogController::class, 'index'])->name('blog.index');

Route::get('/actualites/{slug}', [BlogController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('blog.show');


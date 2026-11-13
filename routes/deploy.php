<?php

use App\Http\Controllers\DeployController;
use Illuminate\Support\Facades\Route;

// Chargé sans le groupe « web » (pas de session ni de cookies chiffrés) ni limiteur de débit
// standard (il utilise la table « cache ») : l'adresse doit fonctionner sur une base encore vide,
// avant même que la clé de chiffrement existe. La limitation est faite dans le contrôleur.
Route::post('/_deploy', DeployController::class)->name('deploy');

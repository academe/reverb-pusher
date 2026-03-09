<?php

use App\Http\Controllers\Api\ReverbAppController;
use App\Http\Middleware\AuthenticateApiToken;
use Illuminate\Support\Facades\Route;

Route::middleware(AuthenticateApiToken::class)->group(function () {
    Route::get('reverb-apps', [ReverbAppController::class, 'index']);
    Route::post('reverb-apps', [ReverbAppController::class, 'store']);
    Route::post('reverb-apps/restart', [ReverbAppController::class, 'restart']);
    Route::get('reverb-apps/{reverbApp}', [ReverbAppController::class, 'show']);
});

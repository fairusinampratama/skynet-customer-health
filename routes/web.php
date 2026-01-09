<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/login', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('login');

use App\Http\Controllers\TvController;

Route::get('/tv/areas', [TvController::class, 'areas']);
Route::get('/tv/servers', [TvController::class, 'servers']);
Route::get('/tv/downtime', [TvController::class, 'downtime']);

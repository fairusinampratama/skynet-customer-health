<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TvController;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/login', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('login');

Route::get('/tv/areas', [TvController::class, 'areas']);
Route::get('/tv/servers', [TvController::class, 'servers']);
Route::get('/tv/downtime', [TvController::class, 'downtime']);

// Route to serve report files with specific filename headers
Route::get('/reports/download/{filename}', function ($filename) {
    \Illuminate\Support\Facades\Log::info("Download Reqq: $filename");
    \Illuminate\Support\Facades\Log::info("Checking Path: reports/$filename on public disk");
    
    if (!Storage::disk('public')->exists("reports/$filename")) {
        \Illuminate\Support\Facades\Log::error("File NOT FOUND: reports/$filename");
        abort(404);
    }
    return Storage::disk('public')->download("reports/$filename");
})->name('reports.download');

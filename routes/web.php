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
    $path = "reports/$filename";
    $disk = Storage::disk('public');
    $fullPath = $disk->path($path);

    \Illuminate\Support\Facades\Log::info("Download Req: $filename");
    \Illuminate\Support\Facades\Log::info("Full Path: $fullPath");
    
    if (!$disk->exists($path)) {
        \Illuminate\Support\Facades\Log::error("File NOT FOUND (Exists Check Failed): $path");
        abort(404);
    }

    try {
        return $disk->download($path);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error("Download FAILED: " . $e->getMessage());
        throw $e;
    }
})->name('reports.download');

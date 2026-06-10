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

// TEMP: Debug widget registration - REMOVE AFTER DEBUGGING
Route::get('/debug-widgets-status', function () {
    try {
        $panel = \Filament\Facades\Filament::getPanel('admin');
        $widgets = $panel->getWidgets();
        
        $result = [
            'total_widgets' => count($widgets),
            'widgets' => [],
        ];
        
        foreach ($widgets as $widgetClass) {
            $info = ['class' => $widgetClass, 'exists' => class_exists($widgetClass)];
            
            try {
                $widget = new $widgetClass();
                $info['isLazy'] = $widgetClass::isLazy();
                
                if (method_exists($widget, 'getHeading')) {
                    $ref = new \ReflectionMethod($widget, 'getHeading');
                    $ref->setAccessible(true);
                    $info['heading'] = $ref->invoke($widget);
                }
                
                if (method_exists($widget, 'getData')) {
                    $ref = new \ReflectionMethod($widget, 'getData');
                    $ref->setAccessible(true);
                    $data = $ref->invoke($widget);
                    $info['data_keys'] = array_keys($data);
                    $info['data_labels'] = $data['labels'] ?? 'N/A';
                }
                
                $info['status'] = 'OK';
            } catch (\Throwable $e) {
                $info['status'] = 'ERROR';
                $info['error'] = $e->getMessage();
                $info['file'] = $e->getFile() . ':' . $e->getLine();
            }
            
            $result['widgets'][] = $info;
        }
        
        // Check Filament cache
        $cacheFile = base_path('bootstrap/cache/filament/panels/admin.php');
        $result['cache_exists'] = file_exists($cacheFile);
        
        // Check git commit
        $gitHead = base_path('.git/HEAD');
        if (file_exists($gitHead)) {
            $ref = file_get_contents($gitHead);
            $refFile = base_path('.git/' . trim(str_replace('ref: ', '', $ref)));
            if (file_exists($refFile)) {
                $result['git_commit'] = substr(trim(file_get_contents($refFile)), 0, 7);
            }
        }
        
        return response()->json($result, 200, [], JSON_PRETTY_PRINT);
    } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage(), 'file' => $e->getFile() . ':' . $e->getLine()], 500);
    }
});

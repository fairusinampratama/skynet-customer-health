<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} - Monitor</title>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    @filamentStyles
    @vite(['resources/css/filament/admin/theme.css', 'resources/js/app.js'])
    
    <style>
        /* Hide scrollbars for TV look */
        ::-webkit-scrollbar { display: none; }
        body { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Smooth scrolling */
        html { scroll-behavior: smooth; }
    </style>
</head>
<body class="antialiased bg-gray-900 text-gray-100 min-h-screen overflow-y-auto overflow-x-hidden">
    <!-- Zoom Wrapper -->
    <main id="tv-container" class="w-full origin-top-left p-6 transition-transform duration-300">
        @yield('content')
    </main>

    @filamentScripts
    @vite('resources/js/app.js')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // 1. Handle Zoom via URL param (e.g. ?zoom=1.5)
            const params = new URLSearchParams(window.location.search);
            const zoom = parseFloat(params.get('zoom')) || 1.0;
            const container = document.getElementById('tv-container');
            
            if (zoom !== 1.0) {
                container.style.transform = `scale(${zoom})`;
                // Adjust width to prevent horizontal scroll after zoom
                container.style.width = `${100 / zoom}%`;
                container.style.height = `${100 / zoom}%`; 
            }
        });
    </script>
</body>
</html>

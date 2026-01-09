@extends('layouts.tv')

@section('content')
    <div class="p-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-6 uppercase tracking-wider">
            Critical Downtime Logs
        </h1>
        @livewire(\App\Filament\Admin\Widgets\DowntimeMonitoringBoard::class)
    </div>

    {{-- Auto Scroll Script (Reused from Areas) --}}
    <div x-data="tvTablesScroll"></div>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('tvTablesScroll', () => ({
                init() {
                    // Initialize global state if missing
                    if (!window.tvScrollState) {
                        window.tvScrollState = {
                            running: false,
                            direction: 'down',
                            lastTimestamp: 0
                        };
                    }

                    if (window.tvScrollState.running) return;
                    window.tvScrollState.running = true;

                    this.startLoop();
                },

                startLoop() {
                    const scrollSpeed = 30; // Slower for text reading
                    const pauseDuration = 3000;
                    
                    const loop = (timestamp) => {
                        if (!window.tvScrollState.lastTimestamp) window.tvScrollState.lastTimestamp = timestamp;
                        const deltaTime = timestamp - window.tvScrollState.lastTimestamp;
                        window.tvScrollState.lastTimestamp = timestamp;

                        const totalHeight = document.body.scrollHeight;
                        const visibleHeight = window.innerHeight;
                        const maxScroll = totalHeight - visibleHeight;

                        if (maxScroll <= 0) {
                            requestAnimationFrame(loop);
                            return;
                        }

                        if (window.tvScrollState.direction === 'down') {
                            if (window.scrollY < maxScroll - 2) {
                                window.scrollBy(0, 1);
                            } else {
                                window.tvScrollState.direction = 'up';
                                setTimeout(() => {
                                    window.tvScrollState.lastTimestamp = 0;
                                    requestAnimationFrame(loop); 
                                }, pauseDuration);
                                return;
                            }
                        } else {
                            if (window.scrollY > 0) {
                                window.scrollBy(0, -2);
                            } else {
                                window.tvScrollState.direction = 'down';
                                setTimeout(() => {
                                    window.tvScrollState.lastTimestamp = 0;
                                    requestAnimationFrame(loop);
                                }, pauseDuration);
                                return;
                            }
                        }

                        const delay = window.tvScrollState.direction === 'down' 
                            ? (1000 / scrollSpeed) 
                            : (1000 / (scrollSpeed * 2));
                            
                        setTimeout(() => { requestAnimationFrame(loop); }, delay);
                    };

                    requestAnimationFrame(loop);
                }
            }));
        });
    </script>
@endsection

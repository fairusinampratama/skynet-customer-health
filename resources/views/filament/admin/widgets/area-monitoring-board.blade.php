<x-filament-widgets::widget>
    <x-filament::section :is-collapsible="true" class="">
        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-primary-50 dark:bg-primary-900/10 rounded-lg">
                    <x-filament::icon
                        icon="heroicon-o-signal"
                        class="w-8 h-8 text-primary-600 dark:text-primary-400"
                    />
                </div>
                <div>
                    <h2 class="text-lg sm:text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                        Area Connectivity
                    </h2>
                    <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400 font-medium max-w-xs truncate">
                        {{ count($this->areas) }} monitored areas
                    </p>
                </div>
            </div>

            <div class="flex p-1 bg-gray-100 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800">
                @foreach([
                    'table' => ['label' => 'List', 'icon' => 'heroicon-m-list-bullet'],
                    'card'  => ['label' => 'Grid', 'icon' => 'heroicon-m-squares-2x2'],
                    'chart' => ['label' => 'Graph', 'icon' => 'heroicon-m-chart-bar'],
                ] as $mode => $data)
                    <button wire:click="setMode('{{ $mode }}')" 
                        class="flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200 
                        {{ $displayMode === $mode 
                            ? 'bg-white dark:bg-gray-800 text-primary-600 dark:text-primary-400 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10' 
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-50/50 dark:hover:bg-gray-800/50' 
                        }}">
                        <x-filament::icon icon="{{ $data['icon'] }}" class="w-4 h-4" />
                        <span class="hidden sm:inline">{{ $data['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Content Area --}}
        <div class="animate-in fade-in slide-in-from-bottom-2 duration-300">
            
            {{-- Logic for explicit classes to ensure Tailwind scans them --}}
            @php
                $getHealthStatus = function($score) {
                    if ($score >= 80) {
                        return [
                            'name' => 'success',
                            'badge' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400',
                            'bar' => 'bg-success-500',
                            'text' => 'text-success-600 dark:text-success-400',
                        ];
                    } elseif ($score >= 65) {
                        return [
                            'name' => 'warning',
                            'badge' => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400',
                            'bar' => 'bg-warning-500',
                            'text' => 'text-warning-600 dark:text-warning-400',
                        ];
                    } else {
                        return [
                            'name' => 'danger',
                            'badge' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400',
                            'bar' => 'bg-danger-500',
                            'text' => 'text-danger-600 dark:text-danger-400',
                        ];
                    }
                };
            @endphp

            @if($displayMode === 'table')
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead class="text-base text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="px-6 py-4 font-semibold tracking-wider">Area Name</th>
                                <th class="px-6 py-4 font-semibold text-center tracking-wider text-danger-600 dark:text-danger-400">Offline</th>
                                <th class="px-6 py-4 font-semibold text-center tracking-wider text-success-600 dark:text-success-400">Online</th>
                                <th class="px-6 py-4 font-semibold text-center tracking-wider">Total</th>
                                <th class="px-6 py-4 font-semibold text-center tracking-wider">Health</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-900">
                            @foreach($this->areas as $area)
                                @php $status = $getHealthStatus($area->health_score); @endphp
                                <tr class="group hover:bg-gray-50 dark:hover:bg-white/5 transition-colors cursor-pointer" 
                                    onclick="window.location='{{ route('filament.admin.resources.areas.edit', $area->id) }}'">
                                    <th class="px-6 py-5 font-semibold text-lg text-gray-900 dark:text-white whitespace-nowrap uppercase">
                                        {{ $area->name }}
                                    </th>
                                    <td class="px-6 py-5 text-center">
                                        @if($area->down_count > 0)
                                            <span class="inline-flex items-center px-4 py-1.5 rounded-md text-base font-bold bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-400 ring-1 ring-inset ring-danger-600/20">
                                                {{ $area->down_count }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-600 font-mono text-base">0</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <span class="inline-flex items-center px-4 py-1.5 rounded-md text-base font-bold bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-400 ring-1 ring-inset ring-success-600/20">
                                            {{ $area->up_count }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 text-center text-gray-500 dark:text-gray-400 font-mono text-base">
                                        / {{ $area->total_count }}
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <div class="flex items-center justify-center gap-3">
                                            {{-- Progress Bar (Fixed Width, No Shrink) --}}
                                            <div class="w-32 h-3.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden shrink-0 shadow-inner">
                                                <div class="h-full rounded-full {{ $status['bar'] }} transition-all duration-500" style="width: {{ $area->health_score }}%"></div>
                                            </div>
                                            {{-- Percentage (Fixed Width, Monospace) --}}
                                            <span class="w-14 text-right text-sm font-bold font-mono shrink-0 {{ $status['text'] }}">
                                                {{ $area->health_score }}%
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @elseif($displayMode === 'card')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                    @foreach($this->areas as $area)
                        @php $status = $getHealthStatus($area->health_score); @endphp
                        <a href="{{ route('filament.admin.resources.areas.edit', $area->id) }}" class="flex flex-col h-full relative p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm hover:shadow-md hover:border-primary-200 dark:hover:border-primary-900 transition-all duration-300 group">
                            
                            {{-- Top Row --}}
                            <div class="flex justify-between items-start mb-4 sm:mb-6 gap-2">
                                <div class="overflow-hidden">
                                    <h3 class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white truncate uppercase" title="{{ $area->name }}">
                                        {{ $area->name }}
                                    </h3>
                                    <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $area->total_count }} Hosts
                                    </p>
                                </div>
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 sm:px-2.5 sm:py-1 text-xs sm:text-sm font-bold ring-1 ring-inset whitespace-nowrap {{ $status['badge'] }}">
                                    {{ $area->health_score }}%
                                </span>
                            </div>

                            {{-- Stats Row (Conditional) --}}
                            @if($area->down_count > 0)
                                {{-- SCENARIO B: Issues Detected (Split View) --}}
                                <div class="grid grid-cols-2 gap-3 sm:gap-4 mb-4 sm:mb-6">
                                    <div class="flex flex-col p-3 sm:p-4 rounded-xl bg-danger-50 dark:bg-danger-900/10 border border-danger-100 dark:border-danger-900/20">
                                        <span class="text-xs sm:text-sm font-bold text-danger-600/80 dark:text-danger-400 uppercase tracking-wider mb-1">Offline</span>
                                        <span class="font-bold text-danger-600 dark:text-danger-500 {{ strlen($area->down_count) > 2 ? 'text-2xl sm:text-4xl' : 'text-3xl sm:text-5xl' }}">
                                            {{ $area->down_count }}
                                        </span>
                                    </div>
                                    <div class="flex flex-col p-3 sm:p-4 rounded-xl bg-success-50 dark:bg-success-900/10 border border-success-100 dark:border-success-900/20 text-right">
                                        <span class="text-xs sm:text-sm font-bold text-success-600/80 dark:text-success-400 uppercase tracking-wider mb-1">Online</span>
                                        <span class="font-bold text-success-600 dark:text-success-500 {{ strlen($area->up_count) > 2 ? 'text-2xl sm:text-4xl' : 'text-3xl sm:text-5xl' }}">
                                            {{ $area->up_count }}
                                        </span>
                                    </div>
                                </div>
                            @else
                                {{-- SCENARIO A: All Good (Unified View) --}}
                                <div class="flex-1 flex flex-col items-center justify-center py-4 mb-4 sm:mb-6 border-y border-dashed border-success-200 dark:border-success-900/30 bg-success-50/30 dark:bg-success-900/5 rounded-lg">
                                    <span class="text-5xl sm:text-7xl font-black text-success-600 dark:text-success-400 tracking-tighter drop-shadow-sm">
                                        {{ $area->total_count }}
                                    </span>
                                    <span class="text-xs sm:text-sm font-bold text-success-600/70 dark:text-success-400/70 uppercase tracking-widest mt-1">
                                        Active Hosts
                                    </span>
                                </div>
                            @endif

                            {{-- Progress Bar --}}
                            <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden mb-3">
                                <div class="h-full rounded-full {{ $status['bar'] }}" style="width: {{ $area->health_score }}%"></div>
                            </div>
                            
                            {{-- Footer --}}
                            @if($area->down_count > 0)
                                <div class="mt-auto flex items-center justify-center gap-2 text-xs sm:text-sm font-bold text-danger-600 dark:text-danger-400 bg-danger-50 dark:bg-danger-900/20 py-2 px-3 rounded-lg">
                                    <x-filament::icon icon="heroicon-m-exclamation-triangle" class="w-4 h-4 sm:w-5 sm:h-5" />
                                    {{ $area->down_count }} Offline
                                </div>
                            @else
                                <div class="mt-auto flex items-center justify-center gap-2 text-xs sm:text-sm font-bold text-success-600 dark:text-success-400 bg-success-50 dark:bg-success-900/20 py-2 px-3 rounded-lg">
                                    <x-filament::icon icon="heroicon-m-check-circle" class="w-4 h-4 sm:w-5 sm:h-5" />
                                    100% Online
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>

            @elseif($displayMode === 'chart')
                <div class="space-y-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5">
                    @foreach($this->areas as $area)
                    @php $status = $getHealthStatus($area->health_score); @endphp
                    
                    <a href="{{ route('filament.admin.resources.areas.edit', $area->id) }}" class="flex items-center gap-4 sm:gap-6 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-white/5 transition-colors group">
                        
                        {{-- 1. Identity (Name & Total) --}}
                        <div class="w-24 sm:w-32 shrink-0">
                            <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white truncate uppercase" title="{{ $area->name }}">
                                {{ $area->name }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $area->total_count }} Hosts
                            </p>
                        </div>

                        {{-- 2. The Visualization (Bar) --}}
                        <div class="flex-1 h-3 sm:h-4 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden flex">
                            {{-- Green Segment --}}
                            @if($area->up_count > 0)
                                <div class="h-full {{ $status['bar'] }} transition-all duration-500" style="width: {{ ($area->up_count / $area->total_count) * 100 }}%"></div>
                            @endif
                            {{-- Red Segment --}}
                            @if($area->down_count > 0)
                                <div class="h-full bg-danger-500 transition-all duration-500" style="width: {{ ($area->down_count / $area->total_count) * 100 }}%"></div>
                            @endif
                        </div>

                        {{-- 3. The Metrics (Aligned Right) --}}
                        <div class="flex items-center justify-end gap-3 sm:gap-4 w-32 sm:w-48 shrink-0">
                            {{-- Counts --}}
                            <div class="text-right flex flex-col sm:flex-row sm:gap-3">
                                <span class="text-xs sm:text-sm font-bold text-success-600 dark:text-success-400">
                                    {{ $area->up_count }} <span class="hidden sm:inline text-xs opacity-75">UP</span>
                                </span>
                                @if($area->down_count > 0)
                                    <span class="text-xs sm:text-sm font-bold text-danger-600 dark:text-danger-400">
                                        {{ $area->down_count }} <span class="hidden sm:inline text-xs opacity-75">DOWN</span>
                                    </span>
                                @endif
                            </div>

                            {{-- Badge --}}
                            <span class="shrink-0 inline-flex items-center justify-center rounded-md px-2 py-1 text-xs font-bold ring-1 ring-inset {{ $status['badge'] }} w-12 sm:w-14">
                                {{ $area->health_score }}%
                            </span>
                        </div>
                    </a>
                    @endforeach
                </div>
            @endif
        </div>
    </x-filament::section>
    <div x-data="tvAutoScroll"></div>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('tvAutoScroll', () => ({
                init() {
                    if (!window.location.pathname.includes('/tv/')) return;
                    
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
                    const scrollSpeed = 50; // pixels per second
                    const pauseDuration = 5000;
                    
                    const loop = (timestamp) => {
                        // Calculate delta time for smooth speed regardless of frame rate
                        if (!window.tvScrollState.lastTimestamp) window.tvScrollState.lastTimestamp = timestamp;
                        const deltaTime = timestamp - window.tvScrollState.lastTimestamp;
                        window.tvScrollState.lastTimestamp = timestamp;

                        const totalHeight = document.body.scrollHeight;
                        const visibleHeight = window.innerHeight;
                        const maxScroll = totalHeight - visibleHeight;

                        // If content fits on screen, just keep checking
                        if (maxScroll <= 0) {
                            requestAnimationFrame(loop);
                            return;
                        }

                        // Bottom detection with fuzz factor (5px)
                        if (window.tvScrollState.direction === 'down') {
                            if (window.scrollY < maxScroll - 5) {
                                // Scroll down 1px
                                window.scrollBy(0, 1);
                                // Throttle speed simply by setTimeout recursion? 
                                // No, requestAnimationFrame is 60fps.
                                // To control speed, we need logic. 
                                // But simpler: existing logic worked for 'down', failed for 'up'.
                                // Let's keep the simple setTimeout approach but use global direction.
                            } else {
                                window.tvScrollState.direction = 'up';
                                setTimeout(() => {
                                    window.tvScrollState.lastTimestamp = 0; // reset
                                    requestAnimationFrame(loop); 
                                }, pauseDuration);
                                return; // Stop this frame, resume after timeout
                            }
                        } else {
                            // Up logic
                            if (window.scrollY > 0) {
                                window.scrollBy(0, -2); // Fast rewind
                            } else {
                                window.tvScrollState.direction = 'down';
                                setTimeout(() => {
                                    window.tvScrollState.lastTimestamp = 0;
                                    requestAnimationFrame(loop);
                                }, pauseDuration);
                                return;
                            }
                        }

                        // Recursion control
                        // We used setTimeout before to throttle frame rate.
                        // Let's stick to setTimeout for consistency with previous working "down" logic,
                        // but use the GLOBAL direction.
                        
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
</x-filament-widgets::widget>

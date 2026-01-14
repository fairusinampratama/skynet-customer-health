<x-filament-widgets::widget wire:poll.30s>
    <x-filament::section :is-collapsible="true" class="">
        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-primary-50 dark:bg-primary-900/10 rounded-lg">
                    <x-filament::icon
                        icon="heroicon-o-server"
                        class="w-8 h-8 text-primary-600 dark:text-primary-400"
                    />
                </div>
                <div>
                    <h2 class="text-lg sm:text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                        Core Infrastructure
                    </h2>
                    <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400 font-medium max-w-xs truncate">
                        {{ count($this->getServers()) }} monitored servers
                    </p>
                </div>
            </div>

            <div class="flex p-1 bg-gray-100 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800">
                @foreach([
                    'table'     => ['label' => 'List',      'icon' => 'heroicon-m-list-bullet'],
                    'card'      => ['label' => 'Grid',      'icon' => 'heroicon-m-squares-2x2'],
                    'wallboard' => ['label' => 'Wallboard', 'icon' => 'heroicon-m-view-columns'],
                ] as $mode => $data)
                    <button wire:click="$dispatch('set-server-view-mode', { mode: '{{ $mode }}' })" 
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
            @php
                $servers = $this->getServers();
                $upCount = $servers->where('status', 'up')->count();
                $downCount = $servers->where('status', 'down')->count();
                $unstableCount = $servers->where('status', 'unstable')->count();
            @endphp

            @if($displayMode === 'table')
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead class="text-base text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="px-6 py-4 font-semibold tracking-wider">Server Name</th>
                                <th class="px-6 py-4 font-semibold tracking-wider">IP Address</th>
                                <th class="px-6 py-4 font-semibold text-center tracking-wider">Status</th>
                                <th class="px-6 py-4 font-semibold text-center tracking-wider">Latency</th>
                                <th class="px-6 py-4 font-semibold text-center tracking-wider">Last Seen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-900">
                            @foreach($servers as $server)
                                <tr class="group hover:bg-gray-50 dark:hover:bg-white/5 transition-colors cursor-pointer" 
                                    onclick="window.location='{{ route('filament.admin.resources.servers.edit', $server->id) }}'">
                                    <th class="px-6 py-5 font-semibold text-lg text-gray-900 dark:text-white whitespace-nowrap uppercase">
                                        {{ $server->name }}
                                    </th>
                                    <td class="px-6 py-5 font-mono text-gray-600 dark:text-gray-400">
                                        {{ $server->ip_address }}
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <span class="inline-flex items-center px-4 py-1.5 rounded-md text-base font-bold 
                                            {{ $server->status === 'up' ? 'bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-400 ring-1 ring-inset ring-success-600/20' : '' }}
                                            {{ $server->status === 'down' ? 'bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-400 ring-1 ring-inset ring-danger-600/20' : '' }}
                                            {{ $server->status === 'unstable' ? 'bg-warning-50 text-warning-700 dark:bg-warning-400/10 dark:text-warning-400 ring-1 ring-inset ring-warning-600/20' : '' }}">
                                            {{ strtoupper($server->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5 text-center text-gray-600 dark:text-gray-400 font-mono">
                                        {{ $server->latency ? round($server->latency) . 'ms' : '-' }}
                                    </td>
                                    <td class="px-6 py-5 text-center text-gray-500 dark:text-gray-400">
                                        {{ $server->last_seen ? $server->last_seen->diffForHumans() : 'Never' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            @elseif($displayMode === 'card' || $displayMode === 'wallboard')
                @php
                    // Wallboard Dynamic Grid Calculation
                    $gridStyle = '';
                    $wallboardClass = '';
                    
                    if ($displayMode === 'wallboard') {
                        $count = $servers->count();
                        $ratio = 1.8;
                        $rows = max(1, ceil(sqrt($count / $ratio)));
                        $cols = ceil($count / $rows);
                        
                        $gridStyle = "grid-template-columns: repeat($cols, minmax(0, 1fr)); grid-template-rows: repeat($rows, minmax(0, 1fr));";
                        $wallboardClass = 'overflow-hidden';
                    }
                @endphp

                <div x-data="{ 
                        style: '{{ $gridStyle }}',
                        height: '600px',
                        init() {
                            this.calculateHeight();
                            window.addEventListener('resize', () => this.calculateHeight());
                            setTimeout(() => this.calculateHeight(), 100);
                            setTimeout(() => this.calculateHeight(), 500);
                        },
                        calculateHeight() {
                            if ('{{ $displayMode }}' !== 'wallboard') return;
                            const top = this.$el.getBoundingClientRect().top;
                            const available = window.innerHeight - top - 10;
                            this.height = Math.max(available, 200) + 'px';
                        }
                    }"
                    class="grid gap-4 sm:gap-6 {{ $displayMode === 'wallboard' ? $wallboardClass : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4' }}"
                    :style="'{{ $displayMode === 'wallboard' }}' ? (style + ' height: ' + height) : ''">
                    @foreach($servers as $server)
                        @php $isWallboard = $displayMode === 'wallboard'; @endphp
                        <a href="{{ route('filament.admin.resources.servers.edit', $server->id) }}" 
                            class="flex flex-col {{ $isWallboard ? 'p-2' : 'p-5' }} bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm hover:shadow-md hover:border-primary-200 dark:hover:border-primary-900 transition-all duration-300 group overflow-hidden">
                            
                            {{-- Server Name --}}
                            <h3 class="{{ $isWallboard ? 'text-base' : 'text-lg sm:text-xl' }} font-bold text-gray-900 dark:text-white truncate uppercase {{ $isWallboard ? 'mb-0.5' : 'mb-2' }}" title="{{ $server->name }}">
                                {{ $server->name }}
                            </h3>
                            
                            {{-- IP Address --}}
                            <p class="{{ $isWallboard ? 'text-xs' : 'text-sm' }} font-mono text-gray-500 dark:text-gray-400">
                                {{ $server->ip_address }}
                            </p>
                            
                            {{-- Status & Latency (Centered) --}}
                            <div class="flex-1 flex flex-col justify-center py-2">
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 {{ $isWallboard ? 'text-xs' : 'text-sm' }} font-bold 
                                        {{ $server->status === 'up' ? 'bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-400 ring-1 ring-inset ring-success-600/20' : '' }}
                                        {{ $server->status === 'down' ? 'bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-400 ring-1 ring-inset ring-danger-600/20' : '' }}
                                        {{ $server->status === 'unstable' ? 'bg-warning-50 text-warning-700 dark:bg-warning-400/10 dark:text-warning-400 ring-1 ring-inset ring-warning-600/20' : '' }}">
                                        {{ strtoupper($server->status) }}
                                    </span>
                                    
                                    @if($server->latency)
                                        <span class="{{ $isWallboard ? 'text-lg' : 'text-lg' }} font-bold font-mono {{ $server->latency > 100 ? 'text-warning-600' : 'text-success-600' }}">
                                            {{ round($server->latency) }}ms
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            {{-- Last Seen --}}
                            <div class="mt-auto pt-2 border-t border-gray-100 dark:border-gray-800">
                                <p class="{{ $isWallboard ? 'text-[10px]' : 'text-xs' }} text-gray-500 dark:text-gray-400">
                                    <span class="font-semibold">Last seen:</span> {{ $server->last_seen ? $server->last_seen->diffForHumans() : 'Never' }}
                                </p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </x-filament::section>
    @if($displayMode !== 'wallboard')
        <div x-data="tvAutoScroll"></div>
    @endif
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('tvAutoScroll', () => ({
                init() {
                    if (!window.location.pathname.includes('/tv/')) return;
                    
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

                destroy() {
                    if (window.tvScrollState) {
                        window.tvScrollState.running = false;
                    }
                },

                startLoop() {
                    const scrollSpeed = 50; 
                    const pauseDuration = 5000;
                    
                    const loop = (timestamp) => {
                        if (!window.tvScrollState || !window.tvScrollState.running) return;

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
                            if (window.scrollY < maxScroll - 5) {
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
</x-filament-widgets::widget>

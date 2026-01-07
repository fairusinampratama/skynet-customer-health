<x-filament-widgets::widget>
    <x-filament::section :is-collapsible="true" class="overflow-hidden">
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
                    <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                        Area Connectivity
                    </h2>
                    <p class="text-base text-gray-500 dark:text-gray-400 font-medium max-w-xs truncate">
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
                                    <th class="px-6 py-5 font-semibold text-lg text-gray-900 dark:text-white whitespace-nowrap">
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
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($this->areas as $area)
                        @php $status = $getHealthStatus($area->health_score); @endphp
                        <a href="{{ route('filament.admin.resources.areas.edit', $area->id) }}" class="flex flex-col h-full relative p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm hover:shadow-md hover:border-primary-200 dark:hover:border-primary-900 transition-all duration-300 group">
                            
                            {{-- Top Row --}}
                            <div class="flex justify-between items-start mb-6">
                                <div>
                                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white truncate" title="{{ $area->name }}">
                                        {{ $area->name }}
                                    </h3>
                                    <p class="text-base text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $area->total_count }} Hosts
                                    </p>
                                </div>
                                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-sm font-bold ring-1 ring-inset {{ $status['badge'] }}">
                                    {{ $area->health_score }}%
                                </span>
                            </div>

                            {{-- Stats Row (Balanced Red/Green) --}}
                            <div class="grid grid-cols-2 gap-4 mb-6">
                                <div class="flex flex-col p-4 rounded-xl bg-danger-50 dark:bg-danger-900/10 border border-danger-100 dark:border-danger-900/20">
                                    <span class="text-sm font-bold text-danger-600/80 dark:text-danger-400 uppercase tracking-wider mb-1">Offline</span>
                                    <span class="text-5xl font-bold {{ $area->down_count > 0 ? 'text-danger-600 dark:text-danger-500' : 'text-gray-400 dark:text-gray-500' }}">
                                        {{ $area->down_count }}
                                    </span>
                                </div>
                                <div class="flex flex-col p-4 rounded-xl bg-success-50 dark:bg-success-900/10 border border-success-100 dark:border-success-900/20 text-right">
                                    <span class="text-sm font-bold text-success-600/80 dark:text-success-400 uppercase tracking-wider mb-1">Online</span>
                                    <span class="text-5xl font-bold text-success-600 dark:text-success-500">
                                        {{ $area->up_count }}
                                    </span>
                                </div>
                            </div>

                            {{-- Progress Bar --}}
                            <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden mb-3">
                                <div class="h-full rounded-full {{ $status['bar'] }}" style="width: {{ $area->health_score }}%"></div>
                            </div>
                            
                            {{-- Footer --}}
                            @if($area->down_count > 0)
                                <div class="mt-auto flex items-center gap-2 text-sm font-bold text-danger-600 dark:text-danger-400 bg-danger-50 dark:bg-danger-900/20 py-2 px-3 rounded-lg">
                                    <x-filament::icon icon="heroicon-m-exclamation-triangle" class="w-5 h-5" />
                                    {{ $area->down_count }} Offline
                                </div>
                            @else
                                <div class="mt-auto flex items-center gap-2 text-sm font-bold text-gray-500 dark:text-gray-400 py-2 px-3">
                                    <x-filament::icon icon="heroicon-m-check-circle" class="w-5 h-5" />
                                    No Issues
                            @endif
                        </a>
                    @endforeach
                </div>

            @elseif($displayMode === 'chart')
                <div class="space-y-3 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 p-5">
                    @foreach($this->areas as $area)
                    @php $status = $getHealthStatus($area->health_score); @endphp
                        <a href="{{ route('filament.admin.resources.areas.edit', $area->id) }}" class="block group">
                            <div class="flex justify-between items-end mb-2">
                                <div class="flex items-center gap-3">
                                    <span class="text-base font-bold text-gray-800 dark:text-gray-200 w-32 truncate">{{ $area->name }}</span>
                                    <span class="text-sm text-gray-500 dark:text-gray-500">
                                        {{ $area->total_count }}
                                    </span>
                                </div>
                                <span class="text-sm font-bold {{ $status['text'] }}">
                                    {{ $area->health_score }}%
                                </span>
                            </div>
                            
                            <div class="relative w-full h-10 bg-gray-100 dark:bg-gray-800 rounded-md overflow-hidden flex shadow-inner border border-gray-200 dark:border-gray-700">
                                {{-- Up Bar --}}
                                @if($area->up_count > 0)
                                    <div class="h-full bg-success-500 dark:bg-success-600 flex items-center justify-start px-2 transition-colors relative" 
                                         style="width: {{ ($area->total_count > 0) ? ($area->up_count / $area->total_count) * 100 : 0 }}%">
                                         @if(($area->up_count / $area->total_count) > 0.1)
                                            <span class="text-xs font-bold text-white drop-shadow-md whitespace-nowrap">{{ $area->up_count }} UP</span>
                                         @endif
                                    </div>
                                @endif
                                
                                {{-- Down Bar --}}
                                @if($area->down_count > 0)
                                    <div class="h-full bg-danger-500 dark:bg-danger-600 flex items-center justify-end px-2 transition-colors relative" 
                                         style="width: {{ ($area->total_count > 0) ? ($area->down_count / $area->total_count) * 100 : 0 }}%">
                                         @if(($area->down_count / $area->total_count) > 0.1)
                                            <span class="text-xs font-bold text-white drop-shadow-md whitespace-nowrap">{{ $area->down_count }} DOWN</span>
                                         @endif
                                    </div>
                                @endif
                                
                                {{-- Fallback labels for small bars or 0 counts --}}
                                <div class="absolute inset-x-2 inset-y-0 flex justify-between items-center text-xs font-bold pointer-events-none">
                                    @if(($area->up_count / $area->total_count) <= 0.1 && $area->up_count > 0)
                                        <span class="text-gray-600 dark:text-gray-400 drop-shadow-sm ml-0.5">{{ $area->up_count }} UP</span>
                                    @endif
                                    @if(($area->down_count / $area->total_count) <= 0.1 && $area->down_count > 0)
                                        <span class="text-danger-600 dark:text-danger-400 drop-shadow-sm mr-0.5">{{ $area->down_count }} DOWN</span>
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

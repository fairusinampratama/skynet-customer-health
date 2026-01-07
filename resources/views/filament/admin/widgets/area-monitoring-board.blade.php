<x-filament-widgets::widget>
    <x-filament::section :is-collapsible="true" class="overflow-hidden">
        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-primary-50 dark:bg-primary-900/10 rounded-lg">
                    <x-filament::icon
                        icon="heroicon-o-signal"
                        class="w-5 h-5 text-primary-600 dark:text-primary-400"
                    />
                </div>
                <div>
                    <h2 class="text-lg font-bold tracking-tight text-gray-950 dark:text-white">
                        Area Connectivity
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium max-w-xs truncate">
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
                        <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="px-6 py-3 font-semibold tracking-wider">Area Name</th>
                                <th class="px-6 py-3 font-semibold text-center tracking-wider text-danger-600 dark:text-danger-400">Offline</th>
                                <th class="px-6 py-3 font-semibold text-center tracking-wider">Online</th>
                                <th class="px-6 py-3 font-semibold text-center tracking-wider">Total</th>
                                <th class="px-6 py-3 font-semibold text-center tracking-wider">Health</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-900">
                            @foreach($this->areas as $area)
                                @php $status = $getHealthStatus($area->health_score); @endphp
                                <tr class="group hover:bg-gray-50 dark:hover:bg-white/5 transition-colors cursor-pointer" 
                                    onclick="window.location='{{ route('filament.admin.resources.areas.edit', $area->id) }}'">
                                    <th class="px-6 py-4 font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                                        {{ $area->name }}
                                    </th>
                                    <td class="px-6 py-4 text-center">
                                        @if($area->down_count > 0)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-sm font-bold bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-400 ring-1 ring-inset ring-danger-600/20">
                                                {{ $area->down_count }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-600 font-mono text-sm">0</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                            {{ $area->up_count }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-gray-400 dark:text-gray-500 font-mono text-xs">
                                        / {{ $area->total_count }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-3">
                                            {{-- Progress Bar (Fixed Width, No Shrink) --}}
                                            <div class="w-24 h-2.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden shrink-0 shadow-inner">
                                                <div class="h-full rounded-full {{ $status['bar'] }} transition-all duration-500" style="width: {{ $area->health_score }}%"></div>
                                            </div>
                                            {{-- Percentage (Fixed Width, Monospace) --}}
                                            <span class="w-10 text-right text-xs font-bold font-mono shrink-0 {{ $status['text'] }}">
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
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white truncate max-w-[120px]" title="{{ $area->name }}">
                                        {{ $area->name }}
                                    </h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        {{ $area->total_count }} Hosts
                                    </p>
                                </div>
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $status['badge'] }}">
                                    {{ $area->health_score }}%
                                </span>
                            </div>

                            {{-- Stats Row (Refocused on Offline) --}}
                            <div class="flex items-end justify-between mb-3">
                                <div>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-3xl font-bold tracking-tight {{ $area->down_count > 0 ? 'text-danger-600 dark:text-danger-500' : 'text-success-600 dark:text-success-500' }}">
                                            {{ $area->down_count }}
                                        </span>
                                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Offline</span>
                                    </div>
                                    <p class="text-xs font-medium mt-1 min-h-[1.25em] {{ $area->down_count > 0 ? 'text-danger-600 dark:text-danger-400 animate-pulse' : 'invisible' }}">
                                        {{ $area->down_count > 0 ? 'Attention Needed' : 'Ok' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        {{ $area->up_count }} <span class="text-xs text-gray-500 font-normal">Online</span>
                                    </div>
                                    <div class="text-xs text-gray-400 dark:text-gray-600 mt-0.5">
                                        of {{ $area->total_count }} Total
                                    </div>
                                </div>
                            </div>

                            {{-- Progress Bar --}}
                            <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden mb-3">
                                <div class="h-full rounded-full {{ $status['bar'] }}" style="width: {{ $area->health_score }}%"></div>
                            </div>
                            
                            {{-- Footer --}}
                            @if($area->down_count > 0)
                                <div class="mt-auto flex items-center gap-1.5 text-xs font-medium text-danger-600 dark:text-danger-400 bg-danger-50 dark:bg-danger-900/20 py-1.5 px-2.5 rounded-md">
                                    <x-filament::icon icon="heroicon-m-exclamation-triangle" class="w-3.5 h-3.5" />
                                    {{ $area->down_count }} Offline
                                </div>
                            @else
                                <div class="mt-auto flex items-center gap-1.5 text-xs font-medium text-gray-400 dark:text-gray-500 py-1.5 px-2.5">
                                    <x-filament::icon icon="heroicon-m-check-circle" class="w-3.5 h-3.5" />
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
                            <div class="flex justify-between items-end mb-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200 w-24 truncate">{{ $area->name }}</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ $area->total_count }}
                                    </span>
                                </div>
                                <span class="text-xs font-bold {{ $status['text'] }}">
                                    {{ $area->health_score }}%
                                </span>
                            </div>
                            
                            <div class="relative w-full h-8 bg-gray-100 dark:bg-gray-800 rounded-md overflow-hidden flex shadow-inner border border-gray-200 dark:border-gray-700">
                                {{-- Up Bar --}}
                                @if($area->up_count > 0)
                                    <div class="h-full bg-success-500 dark:bg-success-600 flex items-center justify-start px-2 transition-colors relative" 
                                         style="width: {{ ($area->total_count > 0) ? ($area->up_count / $area->total_count) * 100 : 0 }}%">
                                         @if(($area->up_count / $area->total_count) > 0.1)
                                            <span class="text-[10px] font-bold text-white drop-shadow-md whitespace-nowrap">{{ $area->up_count }} UP</span>
                                         @endif
                                    </div>
                                @endif
                                
                                {{-- Down Bar --}}
                                @if($area->down_count > 0)
                                    <div class="h-full bg-danger-500 dark:bg-danger-600 flex items-center justify-end px-2 transition-colors relative" 
                                         style="width: {{ ($area->total_count > 0) ? ($area->down_count / $area->total_count) * 100 : 0 }}%">
                                         @if(($area->down_count / $area->total_count) > 0.1)
                                            <span class="text-[10px] font-bold text-white drop-shadow-md whitespace-nowrap">{{ $area->down_count }} DOWN</span>
                                         @endif
                                    </div>
                                @endif
                                
                                {{-- Fallback labels for small bars or 0 counts --}}
                                <div class="absolute inset-x-2 inset-y-0 flex justify-between items-center text-[10px] font-bold pointer-events-none">
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

<x-filament-widgets::widget wire:poll.30s>
    <x-filament::section :is-collapsible="true" class="">
        {{-- Header Section --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-danger-50 dark:bg-danger-900/10 rounded-lg">
                    <x-filament::icon
                        icon="heroicon-o-exclamation-triangle"
                        class="w-8 h-8 text-danger-600 dark:text-danger-400"
                    />
                </div>
                <div>
                    <h2 class="text-lg sm:text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                        Active Downtime Logs
                    </h2>
                    <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400 font-medium max-w-xs truncate">
                        {{ count($this->records) }} critical issues
                    </p>
                </div>
            </div>

            <div class="flex p-1 bg-gray-100 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800">
                @foreach([
                    'table' => ['label' => 'List', 'icon' => 'heroicon-m-list-bullet'],
                    'card'  => ['label' => 'Grid', 'icon' => 'heroicon-m-squares-2x2'],
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
            
            @if($displayMode === 'table')
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead class="text-base text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="px-6 py-4 font-semibold tracking-wider">Area</th>
                                <th class="px-6 py-4 font-semibold tracking-wider">Customer</th>
                                <th class="px-6 py-4 font-semibold tracking-wider">IP Address</th>
                                <th class="px-6 py-4 font-semibold text-center tracking-wider">Duration</th>
                                <th class="px-6 py-4 font-semibold text-center tracking-wider">Packet Loss</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-900">
                            @forelse($this->records as $record)
                                <tr class="group hover:bg-gray-50 dark:hover:bg-white/5 transition-colors cursor-pointer" 
                                    onclick="window.location='{{ route('filament.admin.resources.customers.edit', $record->id) }}'">
                                    <td class="px-6 py-5 font-bold text-gray-700 dark:text-gray-300">
                                        {{ $record->area->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-5 font-semibold text-gray-900 dark:text-white">
                                        {{ $record->name }}
                                    </td>
                                    <td class="px-6 py-5 font-mono text-gray-600 dark:text-gray-400">
                                        {{ $record->ip_address }}
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <div class="flex flex-col items-center">
                                            <span class="inline-flex items-center px-4 py-1.5 rounded-md text-base font-bold bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-400 ring-1 ring-inset ring-danger-600/20">
                                                {{ $record->updated_at->diffForHumans(null, true, true) }}
                                            </span>
                                            <span class="text-xs text-gray-400 mt-1 font-mono">
                                                {{ $record->updated_at->format('M d, H:i') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 text-center">
                                        <span class="font-mono font-bold {{ $record->latestHealth?->packet_loss > 0 ? 'text-danger-600' : 'text-gray-400' }}">
                                            {{ $record->latestHealth?->packet_loss ?? 0 }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <x-filament::icon icon="heroicon-o-check-circle" class="w-12 h-12 text-success-500" />
                                            <span class="text-lg font-medium">No active downtime!</span>
                                            <span class="text-sm">All systems operational.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            @elseif($displayMode === 'card')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                    @forelse($this->records as $record)
                        <a href="{{ route('filament.admin.resources.customers.edit', $record->id) }}" 
                            class="flex flex-col p-5 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl shadow-sm hover:shadow-md hover:border-danger-200 dark:hover:border-danger-900 transition-all duration-300 group ring-1 ring-danger-50 dark:ring-danger-900/20">
                            
                            {{-- Header --}}
                            <div class="flex justify-between items-start mb-4">
                                <div class="overflow-hidden">
                                     <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ $record->area->name ?? 'Unknown Area' }}
                                    </span>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate" title="{{ $record->name }}">
                                        {{ $record->name }}
                                    </h3>
                                </div>
                                <x-filament::icon icon="heroicon-m-exclamation-circle" class="w-6 h-6 text-danger-500 shrink-0" />
                            </div>
                            
                            {{-- IP --}}
                            <p class="text-sm font-mono text-gray-500 dark:text-gray-400 mb-4 flex items-center gap-2">
                                <x-filament::icon icon="heroicon-m-globe-alt" class="w-4 h-4" />
                                {{ $record->ip_address }}
                            </p>
                            
                            {{-- Duration Badge --}}
                            <div class="mt-auto pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-between items-end">
                                <div class="flex flex-col">
                                    <span class="text-xs text-gray-400">Down since</span>
                                    <span class="text-xs font-mono font-medium text-gray-600 dark:text-gray-300">
                                        {{ $record->updated_at->format('H:i') }}
                                    </span>
                                </div>
                                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-sm font-bold bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-400 ring-1 ring-inset ring-danger-600/20">
                                    {{ $record->updated_at->diffForHumans(null, true, true) }}
                                </span>
                            </div>
                        </a>
                    @empty
                        <div class="col-span-full flex flex-col items-center justify-center py-12 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/50 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-800">
                            <x-filament::icon icon="heroicon-o-check-circle" class="w-12 h-12 text-success-500 mb-2" />
                            <span class="text-lg font-medium">All systems green</span>
                        </div>
                    @endforelse
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

<x-filament-widgets::widget wire:poll.15s>
    <x-filament::section>
        @if($this->area)
            {{-- Header --}}
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-950 dark:text-white uppercase">
                        {{ $this->area->name }}
                    </h2>
                    <p class="text-gray-500 dark:text-gray-400">
                        Dedicated Monitoring • {{ $this->stats['total'] }} Customers
                    </p>
                </div>
                
                {{-- Health Badge --}}
                <div class="flex items-center gap-4">
                     <div class="text-right">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Health Score</div>
                        <div class="text-3xl font-black {{ $this->stats['score'] >= 80 ? 'text-success-600 dark:text-success-400' : ($this->stats['score'] >= 50 ? 'text-warning-600 dark:text-warning-400' : 'text-danger-600 dark:text-danger-400') }}">
                            {{ $this->stats['score'] }}%
                        </div>
                    </div>
                </div>
            </div>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                    <div class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Total Online</div>
                    <div class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $this->stats['up'] }}</div>
                </div>
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
                   <div class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Total Offline</div>
                    <div class="text-2xl font-bold text-danger-600 dark:text-danger-400">{{ $this->stats['down'] }}</div>
                </div>
            </div>

            {{-- Customer List --}}
            <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                <table class="w-full text-sm text-left border-collapse">
                    <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-800">
                        <tr>
                            <th class="px-6 py-4 font-semibold tracking-wider">Customer Name</th>
                            <th class="px-6 py-4 font-semibold tracking-wider">IP Address</th>
                            <th class="px-6 py-4 font-semibold tracking-wider text-center">Status</th>
                            <th class="px-6 py-4 font-semibold tracking-wider text-right">Latency</th>
                            <th class="px-6 py-4 font-semibold tracking-wider text-right">Packet Loss</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-800 bg-white dark:bg-gray-900">
                        @foreach($this->customers as $customer)
                            <tr class="group hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                    {{ $customer->name }}
                                </td>
                                <td class="px-6 py-4 text-gray-500 dark:text-gray-400 font-mono">
                                    {{ $customer->ip_address }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($customer->status === 'up')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-400">
                                            Online
                                        </span>
                                    @elseif($customer->status === 'down')
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-danger-100 text-danger-800 dark:bg-danger-900/30 dark:text-danger-400 animate-pulse">
                                            Offline
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-warning-100 text-warning-800 dark:bg-warning-900/30 dark:text-warning-400">
                                            {{ ucfirst($customer->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right font-mono {{ $customer->latency_ms > 100 ? 'text-warning-600 dark:text-warning-400' : 'text-gray-600 dark:text-gray-300' }}">
                                    {{ $customer->latency_ms }} ms
                                </td>
                                <td class="px-6 py-4 text-right font-mono {{ $customer->packet_loss > 0 ? 'text-danger-600 dark:text-danger-400 font-bold' : 'text-gray-600 dark:text-gray-300' }}">
                                    {{ $customer->packet_loss }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($this->customers->isEmpty())
                <div class="text-center py-12">
                    <p class="text-gray-500 dark:text-gray-400">No customers found in this area.</p>
                </div>
            @endif

        @else
            <div class="text-center py-12">
                <h3 class="text-lg font-medium text-danger-600 dark:text-danger-400">Area Not Found</h3>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

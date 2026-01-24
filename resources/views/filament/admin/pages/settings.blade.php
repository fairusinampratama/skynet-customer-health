<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Manual Actions</h3>
            <div class="mt-4">
                <x-filament::button 
                    wire:click="sendReport" 
                    wire:loading.attr="disabled"
                    wire:target="sendReport"
                    color="success"
                    icon="heroicon-o-paper-airplane"
                >
                    <span wire:loading.remove wire:target="sendReport">
                        Send Daily Report Now
                    </span>
                    <span wire:loading wire:target="sendReport">
                        Sending... (Please wait)
                    </span>
                </x-filament::button>
            </div>
        </div>
    </form>
</x-filament-panels::page>

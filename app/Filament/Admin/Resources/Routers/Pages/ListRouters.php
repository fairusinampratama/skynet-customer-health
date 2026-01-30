<?php

namespace App\Filament\Admin\Resources\Routers\Pages;

use App\Filament\Admin\Resources\Routers\RouterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRouters extends ListRecords
{
    protected static string $resource = RouterResource::class;

    public function mount(): void
    {
        parent::mount();
        
        // Auto-refresh all active routers when page loads
        $routers = \App\Models\Router::where('is_active', true)->get();
        foreach ($routers as $router) {
            \App\Jobs\CheckRouterHealth::dispatch($router);
        }
        
        if ($routers->isNotEmpty()) {
            \Filament\Notifications\Notification::make()
                ->title('Systems Online')
                ->body('Background health checks initiated.')
                ->success()
                ->send();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

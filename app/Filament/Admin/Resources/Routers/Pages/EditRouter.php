<?php

namespace App\Filament\Admin\Resources\Routers\Pages;

use App\Filament\Admin\Resources\Routers\RouterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRouter extends EditRecord
{
    protected static string $resource = RouterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function afterSave(): void
    {
        \App\Jobs\CheckRouterHealth::dispatch($this->record);
    }
}

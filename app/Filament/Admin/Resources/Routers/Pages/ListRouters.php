<?php

namespace App\Filament\Admin\Resources\Routers\Pages;

use App\Filament\Admin\Resources\Routers\RouterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRouters extends ListRecords
{
    protected static string $resource = RouterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

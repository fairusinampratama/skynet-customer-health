<?php

namespace App\Filament\Admin\Resources\Areas\Pages;

use App\Filament\Admin\Resources\Areas\AreaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArea extends EditRecord
{
    protected static string $resource = AreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Admin\Resources\Areas\Widgets\AreaHealthStats::class,
            \App\Filament\Admin\Resources\Areas\Widgets\AreaStatusPieChart::class,
        ];
    }
}

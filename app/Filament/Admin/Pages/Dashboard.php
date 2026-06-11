<?php

namespace App\Filament\Admin\Pages;

use App\Models\Area;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('month')
                            ->label('Month')
                            ->type('month')
                            ->default(now()->format('Y-m')),
                        Select::make('areaId')
                            ->label('Area')
                            ->placeholder('All areas')
                            ->options(fn (): array => Area::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->native(false),
                    ])
                    ->columns([
                        'md' => 2,
                    ]),
            ]);
    }
}

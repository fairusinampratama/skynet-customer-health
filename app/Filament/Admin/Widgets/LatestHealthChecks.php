<?php

namespace App\Filament\Admin\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Customer;
use Filament\Tables\Columns\TextColumn;

class LatestHealthChecks extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Latest Downtime Logs';
    
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->query(
                \App\Models\Customer::query()
                    ->where('status', 'down')
                    ->with('latestHealth')
                    ->take(10)
            )
            ->columns([
                TextColumn::make('name')->label('Customer'),
                TextColumn::make('status')
                    ->badge()
                    ->color('danger'),
                TextColumn::make('latestHealth.checked_at')
                    ->label('Downtime Started')
                    ->since(),
            ]);
    }
}

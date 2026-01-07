<?php

namespace App\Filament\Admin\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Customer;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class LatestHealthChecks extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Latest Downtime Logs';
    
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->query(
                \App\Models\Customer::query()
                    ->where('status', 'down')
            )
            ->defaultSort('updated_at', 'asc') // Default: Longest downtime first
            ->columns([
                TextColumn::make('index')
                    ->label('#')
                    ->rowIndex(),
                TextColumn::make('name')->label('Customer'),
                TextColumn::make('status')
                    ->badge()
                    ->color('danger'),
                TextColumn::make('updated_at')
                    ->label('Downtime') // Renamed as requested
                    ->since()
                    ->sortable(),
            ])
            ->recordUrl(
                fn (Customer $record): string => \App\Filament\Admin\Resources\Customers\CustomerResource::getUrl('edit', ['record' => $record])
            );
    }
}

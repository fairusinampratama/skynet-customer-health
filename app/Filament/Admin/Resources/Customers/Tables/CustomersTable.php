<?php

namespace App\Filament\Admin\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->columns([
                TextColumn::make('area.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->searchable()
                    ->copyable(),
                \Filament\Tables\Columns\IconColumn::make('is_isolated')
                    ->label('Isolated')
                    ->icon(fn (string $state): ?string => $state ? 'heroicon-m-signal-slash' : null)
                    ->color(fn (string $state): string => $state ? 'warning' : 'gray')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'up' => 'success',
                        'unstable' => 'warning',
                        'down' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('downtime')
                    ->label('Downtime')
                    ->state(fn (\App\Models\Customer $record): string => 
                        $record->status === 'down' 
                            ? $record->updated_at->diffForHumans(null, true, true) 
                            : '-'
                    )
                    ->description(fn (\App\Models\Customer $record): ?string => 
                        $record->status === 'down' 
                            ? $record->updated_at->format('M d, H:i') 
                            : null
                    )
                    ->color(fn (string $state): string => $state !== '-' ? 'danger' : 'gray'),
                TextColumn::make('latency_ms')
                    ->label('Latency')
                    ->suffix(' ms')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => $state > 100 ? 'warning' : 'success'),
                TextColumn::make('packet_loss')
                    ->label('Packet Loss')
                    ->suffix('%')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('last_alerted_at')
                    ->label('Last Alerted')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Last Check')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

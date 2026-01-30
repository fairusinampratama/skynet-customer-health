<?php

namespace App\Filament\Admin\Resources\Routers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoutersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label('IP / Port')
                    ->formatStateUsing(fn ($record) => "{$record->ip_address}:{$record->port}")
                    ->searchable(['ip_address', 'port']),
                
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'up' => 'success',
                        'down' => 'danger',
                        'unstable' => 'warning',
                        default => 'gray',
                    }),
                
                TextColumn::make('cpu_load')
                    ->label('CPU')
                    ->suffix('%')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('temperature')
                    ->label('Temp')
                    ->suffix('°C')
                    ->numeric()
                    ->sortable(),
                    
                TextColumn::make('free_memory')
                    ->label('Free Mem')
                    ->formatStateUsing(function ($state) {
                        return $state ? round($state / 1024 / 1024, 1) . ' MB' : '-';
                    })
                    ->sortable(),
                    
                TextColumn::make('disk_usage')
                    ->label('Disk')
                    ->suffix('%')
                    ->numeric()
                    ->sortable(),
                    
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                TextColumn::make('last_seen')
                    ->since()
                    ->sortable(),
            ])
            ->poll('10s') // Auto-refresh table for monitoring
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

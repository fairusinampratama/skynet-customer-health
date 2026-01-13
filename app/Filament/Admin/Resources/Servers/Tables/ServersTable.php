<?php

namespace App\Filament\Admin\Resources\Servers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('ip_address')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-globe-alt')
                    ->fontFamily('mono'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'up' => 'success',
                        'down' => 'danger',
                        'unstable' => 'warning',
                        default => 'gray',
                    }),
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
                TextColumn::make('last_seen')
                    ->dateTime()
                    ->sortable()
                    ->since(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
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

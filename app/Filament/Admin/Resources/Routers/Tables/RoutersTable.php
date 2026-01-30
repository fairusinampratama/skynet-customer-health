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
                    
                \Filament\Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Active')
                    ->onColor('success')
                    ->offColor('danger')
                    ->afterStateUpdated(function ($record, $state) {
                         if ($state) {
                             \App\Jobs\CheckRouterHealth::dispatch($record);
                         }
                    }),

                TextColumn::make('last_seen')
                    ->since()
                    ->sortable(),
            ])
            ->poll('10s') // Auto-refresh table for monitoring
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\Action::make('refresh')
                    ->label('Refresh Data')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function ($record) {
                        \App\Jobs\CheckRouterHealth::dispatch($record);
                        \Filament\Notifications\Notification::make()
                            ->title('Refreshing...')
                            ->body('Health check queued for ' . $record->name)
                            ->success()
                            ->send();
                    }),
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\BulkAction::make('refresh_selected')
                        ->label('Refresh Selected')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            foreach ($records as $record) {
                                \App\Jobs\CheckRouterHealth::dispatch($record);
                            }
                            \Filament\Notifications\Notification::make()
                                ->title('Refreshing Selected...')
                                ->body('Health checks queued for ' . $records->count() . ' routers.')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}

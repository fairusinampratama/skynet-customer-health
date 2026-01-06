<?php

namespace App\Filament\Admin\Resources\Customers\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HealthChecksRelationManager extends RelationManager
{
    protected static string $relationship = 'healthChecks';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('status')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->defaultSort('checked_at', 'desc')
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'up' => 'success',
                        'unstable' => 'warning',
                        'down' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('latency_ms')
                    ->label('Latency')
                    ->suffix(' ms')
                    ->sortable(),
                TextColumn::make('packet_loss')
                    ->label('Packet Loss')
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('checked_at')
                    ->label('Time')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Read-only
            ])
            ->recordActions([
                // Read-only
            ])
            ->toolbarActions([
                // Read-only
            ]);
    }
}

<?php

namespace App\Filament\Admin\Resources\Areas\RelationManagers;

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

class CustomersRelationManager extends RelationManager
{
    protected static string $relationship = 'customers';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('ip_address')
                    ->required()
                    ->ipv4(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label('IP Address')
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
                TextColumn::make('latestHealth.latency_ms')
                    ->label('Latency')
                    ->suffix(' ms')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => $state > 100 ? 'warning' : 'success'),
                TextColumn::make('latestHealth.packet_loss')
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
                TextColumn::make('latestHealth.checked_at')
                    ->label('Last Check')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordUrl(
                fn ($record): string => \App\Filament\Admin\Resources\Customers\CustomerResource::getUrl('edit', ['record' => $record])
            )
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

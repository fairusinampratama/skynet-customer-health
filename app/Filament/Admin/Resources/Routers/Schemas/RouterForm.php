<?php

namespace App\Filament\Admin\Resources\Routers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RouterForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Connection Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->columnSpan(2),
                        TextInput::make('ip_address')
                            ->required()
                            ->label('IP Address'),
                        TextInput::make('port')
                            ->required()
                            ->numeric()
                            ->default(8728),
                        TextInput::make('username')
                            ->required(),
                        TextInput::make('password')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create'),
                    ]),

                Section::make('Latest Metrics')
                    ->columns(4)
                    ->schema([
                        TextInput::make('cpu_load')
                            ->label('CPU Load')
                            ->suffix('%')
                            ->disabled(),
                        TextInput::make('temperature')
                            ->label('Temperature')
                            ->suffix('°C')
                            ->disabled(),
                        TextInput::make('free_memory')
                            ->label('Free Memory')
                            ->formatStateUsing(fn ($state) => $state ? round($state / 1024 / 1024, 1) . ' MB' : '-')
                            ->disabled(),
                        TextInput::make('disk_usage')
                            ->label('Disk Usage')
                            ->suffix('%')
                            ->disabled(),
                    ]),
                    
                Section::make('Settings')
                    ->schema([
                         Toggle::make('is_active')
                            ->label('Enable Monitoring')
                            ->default(true),
                    ]),

                // Metrics are implicitly shown in index/view, not editable here.
            ]);
    }
}

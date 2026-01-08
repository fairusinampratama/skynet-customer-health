<?php

namespace App\Filament\Admin\Resources\Servers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ServerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('ip_address')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('up'),
                DateTimePicker::make('last_seen'),
            ]);
    }
}

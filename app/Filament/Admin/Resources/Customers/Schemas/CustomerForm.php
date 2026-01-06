<?php

namespace App\Filament\Admin\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('area_id')
                    ->relationship('area', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('location')
                    ->maxLength(255),
                TextInput::make('ip_address')
                    ->required()
                    ->ipv4(),
            ]);
    }
}

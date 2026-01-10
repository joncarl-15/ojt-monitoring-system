<?php

namespace App\Filament\Resources\Students\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class StudentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                TextInput::make('middle_name')
                    ->default(null),
                TextInput::make('course')
                    ->required(),
                TextInput::make('year_level')
                    ->required(),
                TextInput::make('contact_number')
                    ->default(null),
                TextInput::make('email_address')
                    ->email()
                    ->default(null),
                Textarea::make('address')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('company_id')
                    ->numeric()
                    ->default(null),
            ]);
    }
}

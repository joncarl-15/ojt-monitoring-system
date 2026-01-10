<?php

namespace App\Filament\Resources\Announcements\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AnnouncementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('admin_id')
                    ->required()
                    ->numeric(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('announcement_type')
                    ->required()
                    ->default('general'),
                DateTimePicker::make('posted_at')
                    ->required(),
                DatePicker::make('scheduled_date'),
                Toggle::make('is_active')
                    ->required(),
                TextInput::make('company_id')
                    ->numeric()
                    ->default(null),
            ]);
    }
}

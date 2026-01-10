<?php

namespace App\Filament\Resources\DailyTimeRecords\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class DailyTimeRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('student_id')
                    ->required()
                    ->numeric(),
                DatePicker::make('record_date')
                    ->required(),
                DateTimePicker::make('time_in'),
                DateTimePicker::make('time_out'),
                TextInput::make('daily_hours')
                    ->numeric()
                    ->default(null),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}

<?php

namespace App\Filament\Resources\ActivityLogs\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ActivityLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('student_id')
                    ->required()
                    ->numeric(),
                DatePicker::make('week_starting')
                    ->required(),
                DatePicker::make('week_ending')
                    ->required(),
                Textarea::make('task_description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('hours_rendered')
                    ->required()
                    ->numeric(),
                Textarea::make('accomplishments')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
            ]);
    }
}

<?php

namespace App\Filament\Resources\DailyTimeRecords;

use App\Filament\Resources\DailyTimeRecords\Pages\CreateDailyTimeRecord;
use App\Filament\Resources\DailyTimeRecords\Pages\EditDailyTimeRecord;
use App\Filament\Resources\DailyTimeRecords\Pages\ListDailyTimeRecords;
use App\Filament\Resources\DailyTimeRecords\Schemas\DailyTimeRecordForm;
use App\Filament\Resources\DailyTimeRecords\Tables\DailyTimeRecordsTable;
use App\Models\DailyTimeRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DailyTimeRecordResource extends Resource
{
    protected static ?string $model = DailyTimeRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return DailyTimeRecordForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DailyTimeRecordsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDailyTimeRecords::route('/'),
            'create' => CreateDailyTimeRecord::route('/create'),
            'edit' => EditDailyTimeRecord::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources\DailyTimeRecords\Pages;

use App\Filament\Resources\DailyTimeRecords\DailyTimeRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDailyTimeRecords extends ListRecords
{
    protected static string $resource = DailyTimeRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

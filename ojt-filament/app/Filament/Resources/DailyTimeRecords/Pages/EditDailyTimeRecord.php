<?php

namespace App\Filament\Resources\DailyTimeRecords\Pages;

use App\Filament\Resources\DailyTimeRecords\DailyTimeRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDailyTimeRecord extends EditRecord
{
    protected static string $resource = DailyTimeRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}

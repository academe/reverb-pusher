<?php

namespace App\Filament\Admin\Resources\ReverbAppResource\Pages;

use App\Filament\Admin\Resources\ReverbAppResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReverbApps extends ListRecords
{
    protected static string $resource = ReverbAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\ReverbAppResource\Pages;

use App\Filament\Admin\Resources\ReverbAppResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReverbApp extends EditRecord
{
    protected static string $resource = ReverbAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}

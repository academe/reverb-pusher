<?php

namespace App\Filament\Admin\Resources\ReverbAppResource\Pages;

use App\Filament\Admin\Resources\ReverbAppResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewReverbApp extends ViewRecord
{
    protected static string $resource = ReverbAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('App Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('name'),
                        Infolists\Components\TextEntry::make('description'),
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Active')
                            ->boolean(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Credentials')
                    ->schema([
                        Infolists\Components\TextEntry::make('app_id')
                            ->label('App ID')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('app_key')
                            ->label('App Key')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('app_secret')
                            ->label('App Secret')
                            ->copyable(),
                    ])
                    ->columns(1),

                Infolists\Components\Section::make('Configuration')
                    ->schema([
                        Infolists\Components\TextEntry::make('max_connections')
                            ->label('Max Connections'),
                        Infolists\Components\TextEntry::make('allowed_origins')
                            ->label('Allowed Origins')
                            ->listWithLineBreaks(),
                    ])
                    ->columns(2),
            ]);
    }
}

<?php

namespace App\Filament\Admin\Widgets;

use App\Support\LoopbackApp;
use Filament\Widgets\Widget;

class LoopbackAppWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.loopback-app';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'appId' => LoopbackApp::appId(),
            'key' => LoopbackApp::key(),
        ];
    }
}

<?php

namespace App\Observers;

use App\Models\ReverbApp;
use App\Jobs\RestartReverbServer;

class ReverbAppObserver
{
    public function created(ReverbApp $reverbApp): void
    {
        $this->triggerRestart('ReverbApp created');
    }

    public function updated(ReverbApp $reverbApp): void
    {
        $this->triggerRestart('ReverbApp updated');
    }

    public function deleted(ReverbApp $reverbApp): void
    {
        $this->triggerRestart('ReverbApp deleted');
    }

    protected function triggerRestart(string $reason): void
    {
        RestartReverbServer::dispatch($reason);
    }
}
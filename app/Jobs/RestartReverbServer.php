<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RestartReverbServer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $reason = 'Configuration changed'
    ) {}

    public function handle(): void
    {
        try {
            Log::info("Reverb: {$this->reason}, triggering restart");
            
            Artisan::call('reverb:restart');
            
            Log::info('Reverb: Successfully triggered restart via artisan');
            
        } catch (\Exception $e) {
            Log::error('Reverb: Failed to restart server', [
                'reason' => $this->reason,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}
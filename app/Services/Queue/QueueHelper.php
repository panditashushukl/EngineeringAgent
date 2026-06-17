<?php

namespace App\Services\Queue;

use Illuminate\Support\Facades\Log;

class QueueHelper
{
    /**
     * Start a background queue worker to process jobs asynchronously.
     */
    public static function runWorkerInBackground(): void
    {
        // Only run when NOT in testing/phpunit
        if (app()->runningUnitTests()) {
            return;
        }

        try {
            $artisan = base_path('artisan');
            // Execute the worker with a timeout or let it stop when empty
            $command = "php {$artisan} queue:work --stop-when-empty > /dev/null 2>&1 &";
            exec($command);
            Log::info("Background queue worker started: {$command}");
        } catch (\Throwable $e) {
            Log::error("Failed to start background queue worker: " . $e->getMessage());
        }
    }
}

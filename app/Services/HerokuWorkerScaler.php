<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Scales the Heroku worker dyno between 0 and 1 so it only runs while there are queued jobs.
 * Does nothing unless HEROKU_API_TOKEN and HEROKU_APP_NAME are set (i.e. only on Heroku).
 */
class HerokuWorkerScaler
{
    private const RUNNING_FLAG = 'heroku_worker_running';

    public function enabled(): bool
    {
        return (bool) (config('services.heroku.api_token') && config('services.heroku.app_name'));
    }

    /**
     * Start the worker if we haven't already asked for it. Called whenever a job is queued.
     */
    public function ensureRunning(): void
    {
        // Cache::add only succeeds once, so a burst of queued jobs makes one API call
        if (!$this->enabled() || !Cache::add(self::RUNNING_FLAG, true, now()->addHour())) {
            return;
        }

        if (!$this->scale(1)) {
            Cache::forget(self::RUNNING_FLAG); // try again on the next queued job
        }
    }

    /**
     * Called by the worker once the queue is empty: scale to 0, unless jobs are waiting.
     * Returns false when jobs are still queued (the worker should keep going).
     */
    public function scaleDownIfIdle(): bool
    {
        if (!$this->enabled()) {
            return true;
        }
        if ($this->pendingJobs() > 0) {
            return false;
        }

        Cache::forget(self::RUNNING_FLAG);
        $this->scale(0);

        // A job may have been queued between the check and the scale-down; if so, start back up
        if ($this->pendingJobs() > 0) {
            $this->ensureRunning();
        }

        return true;
    }

    public function pendingJobs(): int
    {
        return DB::table(config('queue.connections.database.table', 'jobs'))->count();
    }

    private function scale(int $quantity): bool
    {
        $app = config('services.heroku.app_name');

        try {
            $response = Http::withToken(config('services.heroku.api_token'))
                ->withHeaders(['Accept' => 'application/vnd.heroku+json; version=3'])
                ->timeout(10)
                ->patch("https://api.heroku.com/apps/{$app}/formation/worker", ['quantity' => $quantity]);

            if ($response->successful()) {
                Log::info("Scaled Heroku worker to {$quantity}");
                return true;
            }
            Log::error("Failed to scale Heroku worker to {$quantity}", ['status' => $response->status(), 'body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error("Failed to scale Heroku worker to {$quantity}: " . $e->getMessage());
        }

        return false;
    }
}

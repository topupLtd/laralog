<?php

namespace Topup\Logger\Console;

use Illuminate\Console\Command;
use Topup\Logger\Models\Logger;

class PruneCommand extends Command
{
    protected $signature = 'topup-logger:prune
        {--days= : Delete logs older than this many days (default: topup-logger.prune_days)}
        {--chunk=1000 : How many logs to delete per query}';

    protected $description = 'Delete old request/response logs';

    public function handle()
    {
        $days = $this->option('days') !== null ? $this->option('days') : config('topup-logger.prune_days', 30);
        $chunk = $this->option('chunk');

        if (!ctype_digit((string) $days) || (int) $days < 1) {
            $this->error('--days must be a whole number of 1 or more.');
            return 1;
        }
        if (!ctype_digit((string) $chunk) || (int) $chunk < 1) {
            $this->error('--chunk must be a whole number of 1 or more.');
            return 1;
        }

        $days = (int) $days;
        $chunk = (int) $chunk;
        $cutoff = now()->subDays($days);
        $deleted = 0;

        // small batches keep each delete short, so the app's own log writes are not held up
        do {
            $ids = Logger::query()->where('request_time', '<', $cutoff)->limit($chunk)->pluck('id');
            if ($ids->isNotEmpty()) {
                $deleted += Logger::query()->whereIn('id', $ids->all())->delete();
            }
        } while ($ids->count() === $chunk);

        $this->info("Deleted {$deleted} logs older than {$days} days.");

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\RiderLocationPing;
use Illuminate\Console\Command;

/**
 * Location pings are logged on every rider ping (potentially every 30-60s per
 * active rider), so the table grows continuously. Not scheduled automatically
 * — add `$schedule->command('rider:prune-location-pings')->daily()` in
 * routes/console.php once a production task runner is in place.
 */
class PruneRiderLocationPings extends Command
{
    protected $signature = 'rider:prune-location-pings {--days=30 : Delete pings older than this many days}';

    protected $description = 'Delete rider GPS location pings older than the retention window';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = RiderLocationPing::where('recorded_at', '<', $cutoff)->delete();

        $this->info("Deleted {$deleted} location ping(s) older than {$days} days.");

        return self::SUCCESS;
    }
}

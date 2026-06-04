<?php

namespace Bkhim\Geolocation\Console;

use Bkhim\Geolocation\Models\LoginHistory;
use Illuminate\Console\Command;

class PruneOldDataCommand extends Command
{
    protected $signature = 'geolocation:prune {--dry-run : Show what would be deleted without deleting}';
    protected $description = 'Prune old login history data for GDPR compliance';

    public function handle(): int
    {
        $retentionDays = config('geolocation.user_trait.login_history_retention_days', 30);
        $cutoff = now()->subDays($retentionDays);

        $toDelete = LoginHistory::where('occurred_at', '<', $cutoff)->count();

        if ($toDelete === 0) {
            $this->info("No records older than {$retentionDays} days to prune.");
            return 0;
        }

        $this->info("Found {$toDelete} records older than {$retentionDays} days.");

        if ($this->option('dry-run')) {
            $this->line("Dry run - would delete {$toDelete} records.");
            return 0;
        }

        if (!$this->confirm("Delete {$toDelete} records?")) {
            return 1;
        }

        $deleted = LoginHistory::where('occurred_at', '<', $cutoff)->delete();
        $this->info("Deleted {$deleted} records.");

        return 0;
    }
}
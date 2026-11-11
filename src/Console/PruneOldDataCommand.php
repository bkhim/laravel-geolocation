<?php

namespace Bkhim\Geolocation\Console;

use Bkhim\Geolocation\Models\IpBlocklist;
use Bkhim\Geolocation\Models\LoginHistory;
use Illuminate\Console\Command;

class PruneOldDataCommand extends Command
{
    protected $signature = 'geolocation:prune
                            {--dry-run : Show what would be deleted without deleting}
                            {--days= : Override the configured login-history retention period}
                            {--keep-blocks : Do not remove expired IP blocklist entries}';

    protected $description = 'Prune old login history and expired IP blocklist entries';

    public function handle(): int
    {
        $retentionDays = (int) ($this->option('days')
            ?: config('geolocation.user_trait.login_history_retention_days', 30));

        $cutoff = now()->subDays($retentionDays);
        $dryRun = (bool) $this->option('dry-run');

        // --- Login history -------------------------------------------------
        $logins = LoginHistory::where('occurred_at', '<', $cutoff)->count();

        if ($logins === 0) {
            $this->info("No login records older than {$retentionDays} days.");
        } else {
            $this->line("Login history older than {$retentionDays} days: {$logins} record(s).");
        }

        // --- Expired IP blocks --------------------------------------------
        $blocks = $this->option('keep-blocks') ? 0 : IpBlocklist::where('blocked_until', '<=', now())->count();

        if ($blocks > 0) {
            $this->line("Expired IP blocklist entries: {$blocks}.");
        }

        if ($logins === 0 && $blocks === 0) {
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info('Dry run - nothing was deleted.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Delete the records listed above?')) {
            return self::FAILURE;
        }

        $deletedLogins = $logins > 0 ? LoginHistory::where('occurred_at', '<', $cutoff)->delete() : 0;
        $deletedBlocks = $blocks > 0 ? IpBlocklist::where('blocked_until', '<=', now())->delete() : 0;

        $this->info("Deleted {$deletedLogins} login record(s) and {$deletedBlocks} expired block(s).");

        return self::SUCCESS;
    }
}

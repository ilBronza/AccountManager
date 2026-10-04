<?php

namespace IlBronza\AccountManager\Console;

use IlBronza\AccountManager\Models\UserAccessLog;
use Illuminate\Console\Command;

class PruneAccessLogs extends Command
{
    protected $signature = 'accountmanager:prune-access-logs {--days= : Override the configured retention}';
    protected $description = 'Delete access logs older than the configured retention period';

    public function handle(): int
    {
        $days = filter_var($this->option('days') ?? config('accountmanager.logUserAccess.retention_days', 90), FILTER_VALIDATE_INT);
        if ($days === false || $days < 1) {
            $this->error('Retention must be at least one day.');
            return self::FAILURE;
        }
        $cutoff = now()->subDays($days);
        $deleted = 0;
        do {
            $ids = UserAccessLog::where('visited_at', '<', $cutoff)->orderBy('id')->limit(1000)->pluck('id');
            $deleted += UserAccessLog::whereIn('id', $ids)->delete();
        } while ($ids->count() === 1000);

        $this->info("Deleted {$deleted} access logs.");
        return self::SUCCESS;
    }
}

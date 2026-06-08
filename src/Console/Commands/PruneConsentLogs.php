<?php

namespace Happytodev\BlogrGdpr\Console\Commands;

use Happytodev\BlogrGdpr\Models\ConsentLog;
use Illuminate\Console\Command;

class PruneConsentLogs extends Command
{
    protected $signature = 'blogr-gdpr:prune-logs';
    protected $description = 'Prune old consent logs based on retention_days config';

    public function handle(): int
    {
        $retentionDays = config('blogr-gdpr.consent_log.retention_days', 365);

        $deleted = ConsentLog::where('created_at', '<', now()->subDays($retentionDays))->delete();

        $this->info("Deleted {$deleted} expired consent log entries.");

        return Command::SUCCESS;
    }
}

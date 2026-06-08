<?php

namespace Happytodev\BlogrGdpr\Console\Commands;

use Happytodev\BlogrGdpr\Models\GdprRequest;
use Illuminate\Console\Command;

class ProcessDataRequests extends Command
{
    protected $signature = 'blogr-gdpr:process-requests';
    protected $description = 'Process pending data export/erasure requests';

    public function handle(): int
    {
        $pendingRequests = GdprRequest::where('status', 'pending')->get();

        if ($pendingRequests->isEmpty()) {
            $this->info('No pending data requests to process.');
            return Command::SUCCESS;
        }

        foreach ($pendingRequests as $request) {
            $request->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            $this->info("Processed {$request->request_type} request for {$request->email}");
        }

        $this->info("Processed {$pendingRequests->count()} request(s).");

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\RecurringDonationService;
use Illuminate\Console\Command;

class ProcessRecurringDonationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'donations:process-recurring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and generate scheduled surplus food donations for active recurring donation profiles';

    /**
     * Execute the console command.
     */
    public function handle(RecurringDonationService $service): int
    {
        $this->info('Starting scheduled recurring donations processing...');

        $stats = $service->processDueSchedules();

        $this->info("Processed: {$stats['processed']} schedules | Generated: {$stats['generated']} donations | Skipped: {$stats['skipped']}");

        return Command::SUCCESS;
    }
}

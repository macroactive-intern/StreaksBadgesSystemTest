<?php

namespace App\Console\Commands;

use App\Jobs\ProcessNotificationTriggersJob;
use Illuminate\Console\Command;

class ProcessNotificationsCommand extends Command
{
    protected $signature   = 'notifications:process';
    protected $description = 'Dispatch a job to send all pending notification triggers.';

    public function handle(): int
    {
        ProcessNotificationTriggersJob::dispatch();

        $this->info('Notification processing job dispatched.');

        return self::SUCCESS;
    }
}

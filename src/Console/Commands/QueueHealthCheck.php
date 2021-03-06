<?php

namespace IronGate\Chief\Console\Commands;

use Illuminate\Console\Command;
use IronGate\Chief\Jobs\Queue\HealthCheck;

class QueueHealthCheck extends Command
{
    protected $hidden = true;

    protected $signature = 'queue:health-check';

    protected $description = 'Dispatch a queue job to validate it\'s processing by pinging a health check URL.';

    public function handle()
    {
        if (empty(config('chief.queue.monitor'))) {
            return $this->warn('Queue not being monitored because monitor webhook is not set.');
        }

        dispatch(new HealthCheck);

        $this->info('Fired job onto the queue... standby for transmission!');
    }
}

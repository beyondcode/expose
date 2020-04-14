<?php

namespace App\Commands;

use App\Server\Factory;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class ServeCommand extends Command
{
    protected $signature = 'serve {host=0.0.0.0} {hostname=localhost}';

    protected $description = 'Start the shaft server';

    public function handle()
    {
        (new Factory())
            ->setHost($this->argument('host'))
            ->setHostname($this->argument('hostname'))
            ->createServer()
            ->run();
    }
}

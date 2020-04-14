<?php

namespace App\Commands;

use App\Server\Factory;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class ServeCommand extends Command
{
    protected $signature = 'serve {host=127.0.0.1}';

    protected $description = 'Start the shaft server';

    public function handle()
    {
        (new Factory())
            ->setHost($this->argument('host'))
            ->createServer()
            ->run();
    }
}

<?php

namespace App\Commands;

use App\Server\Factory;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class ServeCommand extends Command
{
    protected $signature = 'serve';

    protected $description = 'Start the shaft server';

    public function handle()
    {
        (new Factory())
            ->createServer()
            ->run();
    }
}

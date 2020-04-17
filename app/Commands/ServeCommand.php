<?php

namespace App\Commands;

use App\Server\Factory;
use LaravelZero\Framework\Commands\Command;
use React\EventLoop\LoopInterface;

class ServeCommand extends Command
{
    protected $signature = 'serve {host=0.0.0.0} {hostname=localhost}';

    protected $description = 'Start the shaft server';

    public function handle()
    {
        (new Factory())
            ->setLoop(app(LoopInterface::class))
            ->setHost($this->argument('host'))
            ->setHostname($this->argument('hostname'))
            ->createServer()
            ->run();
    }
}

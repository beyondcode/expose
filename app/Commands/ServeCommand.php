<?php

namespace App\Commands;

use App\Server\Factory;
use LaravelZero\Framework\Commands\Command;
use React\EventLoop\LoopInterface;

class ServeCommand extends Command
{
    protected $signature = 'serve {hostname=localhost} {host=0.0.0.0}  {--validateAuthTokens}';

    protected $description = 'Start the shaft server';

    public function handle()
    {
        /** @var LoopInterface $loop */
        $loop = app(LoopInterface::class);

        $loop->futureTick(function () {
            $this->info("Expose server running.");
        });

        (new Factory())
            ->setLoop($loop)
            ->setHost($this->argument('host'))
            ->setHostname($this->argument('hostname'))
            ->validateAuthTokens($this->option('validateAuthTokens'))
            ->createServer()
            ->run();
    }
}

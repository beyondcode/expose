<?php

namespace App\Commands;

use App\Client\Factory;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use React\EventLoop\LoopInterface;

class ShareCommand extends Command
{
    protected $signature = 'share {host} {--subdomain=}';

    protected $description = 'Share a local url with a remote shaft server';

    public function handle()
    {
        (new Factory())
            ->setLoop(app(LoopInterface::class))
            ->createClient($this->argument('host'), explode(',', $this->option('subdomain')))
            ->createHttpServer()
            ->run();
    }
}

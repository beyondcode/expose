<?php

namespace App\Commands;

use App\Client\Factory;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use React\EventLoop\LoopInterface;

class ShareCommand extends Command
{
    protected $signature = 'share {host} {--subdomain=} {--auth=}';

    protected $description = 'Share a local url with a remote shaft server';

    public function handle()
    {
        if ($this->option('auth')) {
            $GLOBALS['expose.auth'] = $this->option('auth');
        }

        (new Factory())
            ->setLoop(app(LoopInterface::class))
//            ->setHost('beyond.sh') // TODO: Read from (local/global) config file
//            ->setPort(8080) // TODO: Read from (local/global) config file
            ->createClient($this->argument('host'), explode(',', $this->option('subdomain')))
            ->createHttpServer()
            ->run();
    }
}

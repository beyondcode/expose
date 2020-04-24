<?php

namespace App\Commands;

use App\Client\Factory;
use App\Logger\CliRequestLogger;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class ShareCommand extends Command
{
    protected $signature = 'share {host} {--subdomain=} {--auth=} {--token=}';

    protected $description = 'Share a local url with a remote shaft server';

    protected function configureConnectionLogger()
    {
        app()->bind(CliRequestLogger::class, function () {
            return new CliRequestLogger(new ConsoleOutput());
        });

        return $this;
    }

    public function handle()
    {
        $this->configureConnectionLogger();

        (new Factory())
            ->setLoop(app(LoopInterface::class))
//            ->setHost('beyond.sh') // TODO: Read from (local/global) config file
//            ->setPort(8080) // TODO: Read from (local/global) config file
            ->setAuth($this->option('auth'))
            ->setAuthToken($this->option('token'))
            ->createClient($this->argument('host'), explode(',', $this->option('subdomain')))
            ->createHttpServer()
            ->run();
    }
}

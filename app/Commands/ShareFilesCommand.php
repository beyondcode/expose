<?php

namespace App\Commands;

use App\Client\Factory;
use App\Logger\CliRequestLogger;
use LaravelZero\Framework\Commands\Command;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class ShareFilesCommand extends Command
{
    protected $signature = 'share-files {folder=.} {--name=} {--subdomain=} {--auth=}';

    protected $description = 'Share a local folder with a remote expose server';

    protected function configureConnectionLogger()
    {
        app()->bind(CliRequestLogger::class, function () {
            return new CliRequestLogger(new ConsoleOutput());
        });

        return $this;
    }

    public function handle()
    {
        if (! is_dir($this->argument('folder'))) {
            throw new \InvalidArgumentException('The folder '.$this->argument('folder').' does not exist.');
        }

        $this->configureConnectionLogger();

        (new Factory())
            ->setLoop(app(LoopInterface::class))
            ->setHost(config('expose.host', 'localhost'))
            ->setPort(config('expose.port', 8080))
            ->setAuth($this->option('auth'))
            ->createClient()
            ->shareFolder(
                $this->argument('folder'),
                $this->option('name') ?? '',
                explode(',', $this->option('subdomain'))
            )
            ->createHttpServer()
            ->run();
    }
}

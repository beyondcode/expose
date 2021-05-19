<?php

namespace App\Commands;

use App\Client\Factory;
use App\Logger\CliRequestLogger;
use LaravelZero\Framework\Commands\Command;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\ConsoleOutput;

class ShareFilesCommand extends Command
{
    protected $signature = 'share-files {folder=.} {--name=} {--subdomain=} {--auth=} {--server-host=} {--server-port=}';

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

        $serverHost = $this->option('server-host') ?? config('expose.host', 'localhost');
        $serverPort = $this->option('server-port') ?? config('expose.port', 8080);
        $auth = $this->option('auth') ?? config('expose.auth_token', '');

        (new Factory())
            ->setLoop(app(LoopInterface::class))
            ->setHost($serverHost)
            ->setPort($serverPort)
            ->setAuth($auth)
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

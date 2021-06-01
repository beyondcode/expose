<?php

namespace App\Commands;

use App\Client\Factory;
use React\EventLoop\LoopInterface;

class ShareFilesCommand extends ServerAwareCommand
{
    protected $signature = 'share-files {folder=.} {--name=} {--subdomain=} {--auth=} {--domain=}';

    protected $description = 'Share a local folder with a remote expose server';

    public function handle()
    {
        if (! is_dir($this->argument('folder'))) {
            throw new \InvalidArgumentException('The folder '.$this->argument('folder').' does not exist.');
        }

        $auth = $this->option('auth') ?? config('expose.auth_token', '');

        (new Factory())
            ->setLoop(app(LoopInterface::class))
            ->setHost($this->getServerHost())
            ->setPort($this->getServerPort())
            ->setAuth($auth)
            ->createClient()
            ->shareFolder(
                $this->argument('folder'),
                $this->option('name') ?? '',
                explode(',', $this->option('subdomain')),
                $this->option('domain')
            )
            ->createHttpServer()
            ->run();
    }
}

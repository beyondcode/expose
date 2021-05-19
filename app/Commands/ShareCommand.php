<?php

namespace App\Commands;

use App\Client\Factory;
use React\EventLoop\LoopInterface;

class ShareCommand extends ServerAwareCommand
{
    protected $signature = 'share {host} {--subdomain=} {--auth=} {--dns=}';

    protected $description = 'Share a local url with a remote expose server';

    public function handle()
    {
        $auth = $this->option('auth') ?? config('expose.auth_token', '');

        if (strstr($this->argument('host'), 'host.docker.internal')) {
            config(['expose.dns' => true]);
        }

        if ($this->option('dns') !== null) {
            config(['expose.dns' => empty($this->option('dns')) ? true : $this->option('dns')]);
        }

        (new Factory())
            ->setLoop(app(LoopInterface::class))
            ->setHost($this->getServerHost())
            ->setPort($this->getServerPort())
            ->setAuth($auth)
            ->createClient()
            ->share($this->argument('host'), explode(',', $this->option('subdomain')))
            ->createHttpServer()
            ->run();
    }
}

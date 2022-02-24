<?php

namespace App\Commands;

use App\Client\Factory;
use Illuminate\Support\Str;
use React\EventLoop\LoopInterface;

class ShareCommand extends ServerAwareCommand
{
    protected $signature = 'share {host} {--subdomain=} {--auth=} {--dns=} {--domain=}';

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

        $domain = config('expose.default_domain');
        $subdomain = config('expose.default_subdomain');

        if (! is_null($this->option('server'))) {
            $domain = null;
        }

        if (! is_null($this->option('domain'))) {
            $domain = $this->option('domain');
        }

        if (! is_null($this->option('subdomain'))) {
            $subdomains = explode(',', $this->option('subdomain'));
        } elseif (! is_null($subdomain)) {
            $subdomains = [$subdomain];
        } else {
            $host = Str::beforeLast($this->argument('host'), '.');
            $host = Str::beforeLast($host, ':');
            $subdomains = [Str::slug($host)];
        }

        $this->info('Trying to use custom domain: '.$subdomains[0].PHP_EOL);

        (new Factory())
            ->setLoop(app(LoopInterface::class))
            ->setHost($this->getServerHost())
            ->setPort($this->getServerPort())
            ->setAuth($auth)
            ->createClient()
            ->share(
                $this->argument('host'),
                $subdomains,
                $domain
            )
            ->createHttpServer()
            ->run();
    }
}

<?php

namespace App\Commands;

use App\Client\Factory;
use Illuminate\Support\Str;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShareCommand extends ServerAwareCommand
{
    protected $signature = 'share {host} {--subdomain=} {--auth=} {--basicAuth=} {--dns=} {--domain=}';

    protected $description = 'Share a local url with a remote expose server';

    public function handle()
    {
        $auth = $this->option('auth') ?? config('expose.auth_token', '');
        $this->info('Using auth token: '.$auth, OutputInterface::VERBOSITY_DEBUG);

        if (strstr($this->argument('host'), 'host.docker.internal')) {
            config(['expose.dns' => true]);
        }

        if ($this->option('dns') !== null) {
            config(['expose.dns' => empty($this->option('dns')) ? true : $this->option('dns')]);
        }

        $domain = config('expose.default_domain');

        if (! is_null($this->option('server'))) {
            $domain = null;
        }

        if (! is_null($this->option('domain'))) {
            $domain = $this->option('domain');
        }

        if (! is_null($this->option('subdomain'))) {
            $subdomains = explode(',', $this->option('subdomain'));
            $this->info('Trying to use custom domain: '.$subdomains[0].PHP_EOL, OutputInterface::VERBOSITY_VERBOSE);
        } else {
            $host = Str::beforeLast($this->argument('host'), '.');
            $host = str_replace('https://', '', $host);
            $host = str_replace('http://', '', $host);
            $host = Str::beforeLast($host, ':');
            $subdomains = [Str::slug($host)];
            $this->info('Trying to use custom domain: '.$subdomains[0].PHP_EOL, OutputInterface::VERBOSITY_VERBOSE);
        }

        (new Factory())
            ->setLoop(app(LoopInterface::class))
            ->setHost($this->getServerHost())
            ->setPort($this->getServerPort())
            ->setAuth($auth)
            ->setBasicAuth($this->option('basicAuth'))
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

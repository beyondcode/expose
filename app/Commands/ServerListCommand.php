<?php

namespace App\Commands;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;

class ServerListCommand extends Command
{
    const DEFAULT_SERVER_ENDPOINT = 'https://expose.dev/api/servers';

    protected $signature = 'servers';

    protected $description = 'Set or retrieve the default server to use with Expose.';

    public function handle()
    {
        $servers = collect($this->lookupRemoteServers())->map(function ($server) {
            return [
                'key' => $server['key'],
                'region' => $server['region'],
                'plan' => Str::ucfirst($server['plan']),
            ];
        });

        $this->info('You can connect to a specific server with the --server=key option or set this server as default with the default-server command.');
        $this->info('');

        $this->table(['Key', 'Region', 'Type'], $servers);
    }

    protected function lookupRemoteServers()
    {
        try {
            return Http::withOptions([
                'verify' => false,
            ])->get(config('expose.server_endpoint', static::DEFAULT_SERVER_ENDPOINT))->json();
        } catch (\Throwable $e) {
            return [];
        }
    }
}

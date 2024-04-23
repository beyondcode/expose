<?php

namespace App\Server\Http\Controllers;

use App\Contracts\ConnectionManager;
use App\Http\Controllers\Controller;
use App\Server\Configuration;
use App\Server\Connections\ControlConnection;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class ValidateTunnelController extends Controller
{
    private ConnectionManager $connectionManager;
    private Configuration $configuration;

    public function __construct(
        Configuration $configuration,
        ConnectionManager $connectionManager,
    ) {
        $this->connectionManager = $connectionManager;
        $this->configuration = $configuration;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $key = $request->get('key');

        // Only allow requests with the correct key
        if ($key !== $this->getAuthorizedKey()) {
            $httpConnection->send(
                respond_json(['exists' => false], 401),
            );
            $httpConnection->close();

            return;
        }

        $domain = $request->get('domain');
        if ($domain === null) {
            $httpConnection->send(
                respond_json(['exists' => false, 'error' => 'invalid_domain'], 404),
            );
            $httpConnection->close();

            return;
        }

        // If the domain is the same as the hostname, then it requested the main domain
        $hostname = $this->configuration->hostname();
        if ($hostname === $domain) {
            $this->isSuccessful($httpConnection);

            return;
        }

        // Also allow the admin dashboard
        $adminSubdomain = config('expose.admin.subdomain');
        if ($domain === $adminSubdomain.'.'.$hostname) {
            $this->isSuccessful($httpConnection);

            return;
        }

        // Check if the domain is a tunnel
        $sites = collect($this->connectionManager->getConnections())
            ->filter(function ($site) use ($domain) {
                $isControlConnection = get_class($site) === ControlConnection::class;
                if (! $isControlConnection) {
                    return false;
                }

                $fqdn = sprintf(
                    '%s.%s',
                    $site->subdomain,
                    $site->serverHost,
                );

                return $fqdn === $domain;
            })
            ->map(function (ControlConnection $site) {
                return sprintf(
                    '%s.%s',
                    $site->host,
                    $site->subdomain,
                );
            });

        if ($sites->count() > 0) {
            $this->isSuccessful($httpConnection);

            return;
        }

        $httpConnection->send(respond_json(['exists' => false, 'error' => 'no_tunnel_found'], 404));
        $httpConnection->close();
    }

    private function isSuccessful(ConnectionInterface $connection): void
    {
        $connection->send(respond_json(['exists' => true]));
        $connection->close();
    }

    private function getAuthorizedKey(): string
    {
        return config('expose.validate_tunnel.authorized_key');
    }
}

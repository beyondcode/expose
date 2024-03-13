<?php

namespace App\Server\Http\Controllers;

use App\Contracts\ConnectionManager;
use App\Http\Controllers\Controller;
use App\Server\Connections\ControlConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Ratchet\ConnectionInterface;

class ValidateTunnelController extends Controller
{
    private ConnectionManager $connectionManager;


    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $domain = $request->get('domain');
        if ($domain === null) {
            $httpConnection->send(
                respond_json(['exists' => false, 'error' => 'invalid_domain'], 404),
            );

            return;
        }

        /** @var Collection $sites */
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

        $response = $sites->count() === 0
            ? respond_json(['exists' => false, 'error' => 'no_tunnel_found'], 404)
            : respond_json(['exists' => true, 'sites' => $sites->toArray()]);

        $httpConnection->send($response);
    }
}

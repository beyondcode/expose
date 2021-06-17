<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\ConnectionManager;
use App\Server\Configuration;
use App\Server\Connections\ControlConnection;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class GetSiteDetailsController extends AdminController
{
    /** @var ConnectionManager */
    protected $connectionManager;

    /** @var Configuration */
    protected $configuration;

    public function __construct(ConnectionManager $connectionManager, Configuration $configuration)
    {
        $this->connectionManager = $connectionManager;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $domain = $request->get('site');

        $connectedSite = collect($this->connectionManager->getConnections())
            ->filter(function ($connection) {
                return get_class($connection) === ControlConnection::class;
            })
            ->first(function (ControlConnection $site) use ($domain) {
                return "{$site->subdomain}.{$site->serverHost}" === $domain;
            });

        if (is_null($connectedSite)) {
            $httpConnection->send(
                Message::toString(new Response(404))
            );

            return;
        }

        $httpConnection->send(
            respond_json($connectedSite->toArray())
        );
    }
}

<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\ConnectionManager;
use App\Server\Configuration;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class DisconnectSiteController extends AdminController
{
    /** @var ConnectionManager */
    protected $connectionManager;

    /** @var Configuration */
    protected $configuration;

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        if ($request->has('server_host')) {
            $connection = $this->connectionManager->findControlConnectionForSubdomainAndServerHost($request->get('id'), $request->get('server_host'));
        } else {
            $connection = $this->connectionManager->findControlConnectionForClientId($request->get('id'));
        }

        if (! is_null($connection)) {
            $connection->close();

            $this->connectionManager->removeControlConnection($connection);
        }

        $httpConnection->send(respond_json([
            'sites' => $this->connectionManager->getConnections(),
        ]));
    }
}

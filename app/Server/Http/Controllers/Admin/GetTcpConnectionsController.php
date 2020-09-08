<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\ConnectionManager;
use App\Server\Configuration;
use App\Server\Connections\TcpControlConnection;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class GetTcpConnectionsController extends AdminController
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
        $httpConnection->send(
            respond_json([
                'tcp_connections' => collect($this->connectionManager->getConnections())
                    ->filter(function ($connection) {
                        return get_class($connection) === TcpControlConnection::class;
                    })
                    ->map(function ($site, $siteId) {
                        $site = $site->toArray();
                        $site['id'] = $siteId;

                        return $site;
                    })
                    ->values(),
            ])
        );
    }
}

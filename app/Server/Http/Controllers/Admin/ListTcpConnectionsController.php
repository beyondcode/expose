<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\ConnectionManager;
use App\Server\Configuration;
use App\Server\Connections\TcpControlConnection;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class ListTcpConnectionsController extends AdminController
{
    /** @var ConnectionManager */
    protected $connectionManager;
    /** @var Configuration */
    protected $configuration;

    public function __construct(ConnectionManager $connectionManager, Configuration $configuration)
    {
        $this->connectionManager = $connectionManager;
        $this->configuration = $configuration;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $sites = $this->getView($httpConnection, 'server.tcp.index', [
            'scheme' => $this->configuration->port() === 443 ? 'https' : 'http',
            'configuration' => $this->configuration,
            'connections' => collect($this->connectionManager->getConnections())
                ->filter(function ($connection) {
                    return get_class($connection) === TcpControlConnection::class;
                })
                ->map(function ($connection, $connectionId) {
                    $connection = $connection->toArray();
                    $connection['id'] = $connectionId;

                    return $connection;
                })
                ->values(),
        ]);

        $httpConnection->send(
            respond_html($sites)
        );
    }
}

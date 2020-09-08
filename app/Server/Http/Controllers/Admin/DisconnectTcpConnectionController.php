<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\ConnectionManager;
use App\Server\Configuration;
use App\Server\Connections\TcpControlConnection;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class DisconnectTcpConnectionController extends AdminController
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
        $connection = $this->connectionManager->findControlConnectionForClientId($request->get('id'));

        if (! is_null($connection)) {
            $connection->close();

            $this->connectionManager->removeControlConnection($connection);
        }

        $httpConnection->send(respond_json([
            'tcp_connections' => collect($this->connectionManager->getConnections())
                ->filter(function ($connection) {
                    return get_class($connection) === TcpControlConnection::class;
                })
                ->values(),
        ]));
    }
}

<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\ConnectionManager;
use App\Contracts\UserRepository;
use App\Server\Configuration;
use App\Server\Connections\TcpControlConnection;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class GetTcpConnectionsController extends AdminController
{
    protected $keepConnectionOpen = true;

    /** @var ConnectionManager */
    protected $connectionManager;

    /** @var Configuration */
    protected $configuration;

    /** @var UserRepository */
    protected $userRepository;

    public function __construct(ConnectionManager $connectionManager, Configuration $configuration, UserRepository $userRepository)
    {
        $this->connectionManager = $connectionManager;
        $this->userRepository = $userRepository;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $authTokens = [];
        $connections = collect($this->connectionManager->getConnections())
            ->filter(function ($connection) {
                return get_class($connection) === TcpControlConnection::class;
            })
            ->map(function ($site, $siteId) use (&$authTokens) {
                $site = $site->toArray();
                $site['id'] = $siteId;
                $authTokens[] = $site['auth_token'];

                return $site;
            })
            ->values();

        $this->userRepository->getUsersByTokens($authTokens)
            ->then(function ($users) use ($httpConnection, $connections) {
                $users = collect($users);
                $connections = collect($connections)->map(function ($connection) use ($users) {
                    $connection['user'] = $users->firstWhere('auth_token', $connection['auth_token']);

                    return $connection;
                })->toArray();

                $httpConnection->send(
                    respond_json([
                        'tcp_connections' => $connections,
                    ])
                );

                $httpConnection->close();
            });
    }
}

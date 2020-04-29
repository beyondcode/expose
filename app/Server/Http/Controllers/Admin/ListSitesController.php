<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\ConnectionManager;
use App\Http\Controllers\PostController;
use App\Server\Configuration;
use Clue\React\SQLite\DatabaseInterface;
use Clue\React\SQLite\Result;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use function GuzzleHttp\Psr7\str;
use function GuzzleHttp\Psr7\stream_for;

class ListSitesController extends PostController
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
        try {
            $sites = $this->getView('server.sites.index', [
                'scheme' => $this->configuration->port() === 443 ? 'https' : 'http',
                'configuration' => $this->configuration,
                'sites' => $this->connectionManager->getConnections()
            ]);
        } catch (\Exception $e) {
            dump($e->getMessage());
        }

        $httpConnection->send(
            respond_html($sites)
        );
    }
}

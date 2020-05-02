<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\ConnectionManager;
use App\Http\Controllers\Controller;
use App\Server\Configuration;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use function GuzzleHttp\Psr7\str;
use function GuzzleHttp\Psr7\stream_for;

class ListSitesController extends AdminController
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
            $sites = $this->getView($httpConnection, 'server.sites.index', [
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

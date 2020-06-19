<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\ConnectionManager;
use App\Server\Configuration;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

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
        $sites = $this->getView($httpConnection, 'server.sites.index', [
            'scheme' => $this->configuration->port() === 443 ? 'https' : 'http',
            'configuration' => $this->configuration,
            'sites' => collect($this->connectionManager->getConnections())->map(function ($site, $siteId) {
                $site = $site->toArray();
                $site['id'] = $siteId;

                return $site;
            })->values(),
        ]);

        $httpConnection->send(
            respond_html($sites)
        );
    }
}

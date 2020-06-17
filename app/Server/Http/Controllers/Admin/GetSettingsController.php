<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\ConnectionManager;
use App\Server\Configuration;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class GetSettingsController extends AdminController
{
    /** @var Configuration */
    protected $configuration;

    public function __construct(ConnectionManager $connectionManager, Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $httpConnection->send(
            respond_json([
                'configuration' => $this->configuration,
            ])
        );
    }
}

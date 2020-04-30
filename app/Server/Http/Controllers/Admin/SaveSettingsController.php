<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\ConnectionManager;
use App\Http\Controllers\Controller;
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

class SaveSettingsController extends AdminController
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
        config()->set('expose.admin.validate_auth_tokens', $request->has('validate_auth_tokens'));

        $httpConnection->send(str(new Response(301, [
            'Location' => '/settings'
        ])));
    }
}

<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\ConnectionManager;
use App\HttpServer\Controllers\PostController;
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

    public function __construct(ConnectionManager $connectionManager)
    {
        $this->connectionManager = $connectionManager;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $httpConnection->send(
            respond_html($this->getView(['sites' => $this->connectionManager->getConnections()]))
        );
    }

    protected function getView(array $data)
    {
        $twig = new Environment(
            new ArrayLoader([
                'template' => file_get_contents(base_path('resources/views/admin/sites/index.twig')),
            ])
        );

        return stream_for($twig->render('template', $data));
    }
}

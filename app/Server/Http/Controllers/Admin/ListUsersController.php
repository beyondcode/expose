<?php

namespace App\Server\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Clue\React\SQLite\DatabaseInterface;
use Clue\React\SQLite\Result;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use function GuzzleHttp\Psr7\str;
use function GuzzleHttp\Psr7\stream_for;

class ListUsersController extends AdminController
{
    protected $keepConnectionOpen = true;

    /** @var DatabaseInterface */
    protected $database;

    public function __construct(DatabaseInterface $database)
    {
        $this->database = $database;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $this->database->query('SELECT * FROM users ORDER by created_at DESC')->then(function (Result $result) use ($httpConnection) {
            $httpConnection->send(
                respond_html($this->getView('server.users.index', ['users' => $result->rows]))
            );

            $httpConnection->close();
        }, function (\Exception $exception) use ($httpConnection) {
            $httpConnection->send(respond_html('Something went wrong: '.$exception->getMessage(), 500));

            $httpConnection->close();
        });
    }
}

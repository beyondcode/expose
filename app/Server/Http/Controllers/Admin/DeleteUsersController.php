<?php

namespace App\Server\Http\Controllers\Admin;

use App\HttpServer\Controllers\PostController;
use Clue\React\SQLite\DatabaseInterface;
use Clue\React\SQLite\Result;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use function GuzzleHttp\Psr7\str;
use function GuzzleHttp\Psr7\stream_for;

class DeleteUsersController extends PostController
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
        $this->database->query("DELETE FROM users WHERE id = :id", ['id' => $request->id])
                ->then(function (Result $result) use ($httpConnection) {
                    $httpConnection->send(respond_json(['deleted' => true], 200));
                    $httpConnection->close();
                });
    }
}

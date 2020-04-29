<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\ConnectionManager;
use App\Http\Controllers\PostController;
use Clue\React\SQLite\DatabaseInterface;
use Clue\React\SQLite\Result;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use function GuzzleHttp\Psr7\str;
use function GuzzleHttp\Psr7\stream_for;

class VerifyLoginController extends PostController
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
        $this->database->query("SELECT * FROM users WHERE email = :email", ['email' => $request->email])
            ->then(function (Result $result) use ($httpConnection) {
                if (!is_null($result->rows)) {
                    $httpConnection->send(
                        str(new Response(
                            301,
                            ['Location' => '/users']
                        ))
                    );
                } else {
                    $httpConnection->send(
                        str(new Response(
                            301,
                            ['Location' => '/users']
                        ))
                    );
                }
                $httpConnection->close();
            });
    }
}

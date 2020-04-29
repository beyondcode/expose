<?php

namespace App\Server\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

class StoreUsersController extends AdminController
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
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ], [
            'required' => 'The :attribute field is required.',
        ]);

        if ($validator->fails()) {
            $httpConnection->send(respond_json(['errors' => $validator->getMessageBag()], 401));
            $httpConnection->close();

            return;
        }

        $insertData = [
            'name' => $request->get('name'),
            'auth_token' => (string)Str::uuid()
        ];

        $this->database->query("
            INSERT INTO users (name, auth_token, created_at)
            VALUES (:name, :auth_token, DATETIME('now'))
        ", $insertData)
                ->then(function (Result $result) use ($httpConnection) {
                    $this->database->query("SELECT * FROM users WHERE id = :id", ['id' => $result->insertId])
                        ->then(function (Result $result) use ($httpConnection) {
                            $httpConnection->send(respond_json(['user' => $result->rows[0]], 200));
                            $httpConnection->close();
                        });
                });
    }
}

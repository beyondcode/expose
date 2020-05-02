<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\UserRepository;
use App\Http\Controllers\Controller;
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

    /** @var UserRepository */
    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $this->userRepository
            ->getUsers()
            ->then(function ($users) use ($httpConnection) {
                $httpConnection->send(
                    respond_html($this->getView($httpConnection, 'server.users.index', ['users' => $users]))
                );

                $httpConnection->close();
            });
    }
}

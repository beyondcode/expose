<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\UserRepository;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

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

<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\UserRepository;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class DeleteUsersController extends AdminController
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
        $this->userRepository->deleteUser($request->get('id'))
            ->then(function () use ($httpConnection) {
                $httpConnection->send(respond_json(['deleted' => true], 200));
                $httpConnection->close();
            });
    }
}

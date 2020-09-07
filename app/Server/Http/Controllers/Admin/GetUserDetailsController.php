<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\UserRepository;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class GetUserDetailsController extends AdminController
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
            ->getUserById($request->get('id'))
            ->then(function ($user) use ($httpConnection) {
                $httpConnection->send(
                    respond_json(['user' => $user])
                );

                $httpConnection->close();
            });
    }
}

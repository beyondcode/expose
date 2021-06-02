<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\SubdomainRepository;
use App\Contracts\UserRepository;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class GetUserDetailsController extends AdminController
{
    protected $keepConnectionOpen = true;

    /** @var UserRepository */
    protected $userRepository;

    /** @var SubdomainRepository */
    protected $subdomainRepository;

    public function __construct(UserRepository $userRepository, SubdomainRepository $subdomainRepository)
    {
        $this->userRepository = $userRepository;
        $this->subdomainRepository = $subdomainRepository;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $id = $request->get('id');

        if (! is_numeric($id)) {
            $promise = $this->userRepository->getUserByToken($id);
        } else {
            $promise = $this->userRepository->getUserById($id);
        }

        $promise->then(function ($user) use ($httpConnection) {
            if (is_null($user)) {
                $httpConnection->send(
                    respond_json([], 404)
                );

                $httpConnection->close();

                return;
            }
            $this->subdomainRepository->getSubdomainsByUserId($user['id'])
                    ->then(function ($subdomains) use ($httpConnection, $user) {
                        $httpConnection->send(
                            respond_json([
                                'user' => $user,
                                'subdomains' => $subdomains,
                            ])
                        );

                        $httpConnection->close();
                    });
        });
    }
}

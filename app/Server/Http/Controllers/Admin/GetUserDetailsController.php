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
        $this->userRepository
            ->getUserById($request->get('id'))
            ->then(function ($user) use ($httpConnection, $request) {
                $this->subdomainRepository->getSubdomainsByUserId($request->get('id'))
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

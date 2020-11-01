<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\HostnameRepository;
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

    /** @var HostnameRepository */
    protected $hostnameRepository;

    public function __construct(UserRepository $userRepository, SubdomainRepository $subdomainRepository, HostnameRepository $hostnameRepository)
    {
        $this->userRepository = $userRepository;
        $this->subdomainRepository = $subdomainRepository;
        $this->hostnameRepository = $hostnameRepository;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $this->userRepository
            ->getUserById($request->get('id'))
            ->then(function ($user) use ($httpConnection, $request) {
                $this->subdomainRepository->getSubdomainsByUserId($request->get('id'))
                    ->then(function ($subdomains) use ($httpConnection, $user, $request) {
                        $this->hostnameRepository->getHostnamesByUserId($request->get('id'))
                            ->then(function ($hostnames) use ($httpConnection, $user, $subdomains) {
                                $httpConnection->send(
                                    respond_json([
                                        'user' => $user,
                                        'subdomains' => $subdomains,
                                        'hostnames' => $hostnames,
                                    ])
                                );

                                $httpConnection->close();
                            });
                    });
            });
    }
}

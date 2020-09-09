<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\SubdomainRepository;
use App\Contracts\UserRepository;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class DeleteSubdomainController extends AdminController
{
    protected $keepConnectionOpen = true;

    /** @var SubdomainRepository */
    protected $subdomainRepository;

    /** @var UserRepository */
    protected $userRepository;

    public function __construct(UserRepository $userRepository, SubdomainRepository $subdomainRepository)
    {
        $this->userRepository = $userRepository;
        $this->subdomainRepository = $subdomainRepository;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $this->userRepository->getUserByToken($request->get('auth_token', ''))
            ->then(function ($user) use ($request, $httpConnection) {
                if (is_null($user)) {
                    $httpConnection->send(respond_json(['error' => 'The user does not exist'], 404));
                    $httpConnection->close();

                    return;
                }

                $this->subdomainRepository->deleteSubdomainForUserId($user['id'], $request->get('subdomain'))
                    ->then(function ($deleted) use ($httpConnection) {
                        $httpConnection->send(respond_json(['deleted' => $deleted], 200));
                        $httpConnection->close();
                    });
            });
    }
}

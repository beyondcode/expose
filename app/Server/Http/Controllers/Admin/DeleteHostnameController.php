<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\HostnameRepository;
use App\Contracts\SubdomainRepository;
use App\Contracts\UserRepository;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class DeleteHostnameController extends AdminController
{
    protected $keepConnectionOpen = true;

    /** @var HostnameRepository */
    protected $hostnameRepository;

    /** @var UserRepository */
    protected $userRepository;

    public function __construct(UserRepository $userRepository, HostnameRepository $hostnameRepository)
    {
        $this->userRepository = $userRepository;
        $this->hostnameRepository = $hostnameRepository;
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

                $this->hostnameRepository->deleteHostnameForUserId($user['id'], $request->get('hostname'))
                    ->then(function ($deleted) use ($httpConnection) {
                        $httpConnection->send(respond_json(['deleted' => $deleted], 200));
                        $httpConnection->close();
                    });
            });
    }
}

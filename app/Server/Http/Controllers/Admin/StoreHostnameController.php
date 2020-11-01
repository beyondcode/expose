<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\HostnameRepository;
use App\Contracts\SubdomainRepository;
use App\Contracts\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Ratchet\ConnectionInterface;

class StoreHostnameController extends AdminController
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
        $validator = Validator::make($request->all(), [
            'hostname' => 'required',
        ], [
            'required' => 'The :attribute field is required.',
        ]);

        if ($validator->fails()) {
            $httpConnection->send(respond_json(['errors' => $validator->getMessageBag()], 401));
            $httpConnection->close();

            return;
        }

        $this->userRepository->getUserByToken($request->get('auth_token', ''))
            ->then(function ($user) use ($httpConnection, $request) {
                if (is_null($user)) {
                    $httpConnection->send(respond_json(['error' => 'The user does not exist'], 404));
                    $httpConnection->close();

                    return;
                }

                if ($user['can_specify_hostnames'] === 0) {
                    $httpConnection->send(respond_json(['error' => 'The user is not allowed to reserve hostnames.'], 401));
                    $httpConnection->close();

                    return;
                }

                $insertData = [
                    'user_id' => $user['id'],
                    'hostname' => $request->get('hostname'),
                ];

                $this->hostnameRepository
                    ->storeHostname($insertData)
                    ->then(function ($hostname) use ($httpConnection) {
                        if (is_null($hostname)) {
                            $httpConnection->send(respond_json(['error' => 'The hostname is already taken.'], 422));
                            $httpConnection->close();

                            return;
                        }
                        $httpConnection->send(respond_json(['hostname' => $hostname], 200));
                        $httpConnection->close();
                    });
            });
    }
}

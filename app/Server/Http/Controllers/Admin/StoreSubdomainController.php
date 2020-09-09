<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\SubdomainRepository;
use App\Contracts\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Ratchet\ConnectionInterface;

class StoreSubdomainController extends AdminController
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
        $validator = Validator::make($request->all(), [
            'subdomain' => 'required',
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

                if ($user['can_specify_subdomains'] === 0) {
                    $httpConnection->send(respond_json(['error' => 'The user is not allowed to reserve subdomains.'], 401));
                    $httpConnection->close();

                    return;
                }

                $insertData = [
                    'user_id' => $user['id'],
                    'subdomain' => $request->get('subdomain'),
                ];

                $this->subdomainRepository
                    ->storeSubdomain($insertData)
                    ->then(function ($subdomain) use ($httpConnection) {
                        if (is_null($subdomain)) {
                            $httpConnection->send(respond_json(['error' => 'The subdomain is already taken.'], 422));
                            $httpConnection->close();

                            return;
                        }
                        $httpConnection->send(respond_json(['subdomain' => $subdomain], 200));
                        $httpConnection->close();
                    });
            });
    }
}

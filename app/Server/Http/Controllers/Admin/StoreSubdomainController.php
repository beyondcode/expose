<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\SubdomainRepository;
use App\Contracts\UserRepository;
use function GuzzleHttp\Psr7\str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;

class StoreSubdomainController extends AdminController
{
    protected $keepConnectionOpen = true;

    /** @var SubdomainRepository */
    protected $subdomainRepository;

    public function __construct(SubdomainRepository $subdomainRepository)
    {
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

        $insertData = [
            'user_id' => $request->get('id'),
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
    }
}

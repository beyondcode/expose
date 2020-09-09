<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\SubdomainRepository;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class DeleteSubdomainController extends AdminController
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
        $this->subdomainRepository->deleteSubdomainForUserId($request->get('id'), $request->get('subdomain'))
            ->then(function () use ($httpConnection) {
                $httpConnection->send(respond_json(['deleted' => true], 200));
                $httpConnection->close();
            });
    }
}

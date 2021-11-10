<?php

namespace App\Server\Http\Controllers\Admin;

use App\Contracts\LoggerRepository;
use App\Server\Configuration;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class GetLogsForSubdomainController extends AdminController
{
    protected $keepConnectionOpen = true;

    /** @var Configuration */
    protected $configuration;

    /** @var LoggerRepository */
    protected $logger;

    public function __construct(LoggerRepository $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $subdomain = $request->get('subdomain');
        $this->logger->getLogsBySubdomain($subdomain)
            ->then(function ($logs) use ($httpConnection) {
                $httpConnection->send(
                    respond_json(['logs' => $logs])
                );

                $httpConnection->close();
            });
    }
}

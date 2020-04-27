<?php

namespace App\Server\Http\Controllers;

use App\Contracts\ConnectionManager;
use App\HttpServer\Controllers\PostController;
use App\Server\Configuration;
use App\Server\Connections\ControlConnection;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Nyholm\Psr7\Factory\Psr17Factory;
use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\Frame;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use function GuzzleHttp\Psr7\str;

class TunnelMessageController extends PostController
{
    /** @var ConnectionManager */
    protected $connectionManager;

    /** @var Configuration */
    private $configuration;

    protected $keepConnectionOpen = true;

    protected $middleware = [

    ];

    public function __construct(ConnectionManager $connectionManager, Configuration $configuration)
    {
        $this->connectionManager = $connectionManager;
        $this->configuration = $configuration;
    }

    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $controlConnection = $this->connectionManager->findControlConnectionForSubdomain($this->detectSubdomain($request));

        if (is_null($controlConnection)) {
            $httpConnection->send(str(new Response(404, [], 'Not found')));
            $httpConnection->close();
            return;
        }

        $this->sendRequestToClient($request, $controlConnection, $httpConnection);
    }

    protected function detectSubdomain(Request $request): ?string
    {
        $domainParts = explode('.', $request->getHost());

        return trim($domainParts[0]);
    }

    protected function sendRequestToClient(Request $request, ControlConnection $controlConnection, ConnectionInterface $httpConnection)
    {
        (new Pipeline(app()))
            ->send($this->prepareRequest($request, $controlConnection))
            ->through($this->middleware)
            ->then(function ($request) use ($controlConnection, $httpConnection) {
                $requestId = $request->header('X-Expose-Request-ID');

                $this->connectionManager->storeHttpConnection($httpConnection, $requestId);

                $controlConnection->once('proxy_ready_' . $requestId, function (ConnectionInterface $proxy) use ($request) {
                    // Convert the Laravel request into a PSR7 request
                    $psr17Factory = new Psr17Factory();
                    $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
                    $request = $psrHttpFactory->createRequest($request);

                    $binaryMsg = new Frame(str($request), true, Frame::OP_BINARY);
                    $proxy->send($binaryMsg);
                });

                $controlConnection->registerProxy($requestId);
            });
    }

    protected function prepareRequest(Request $request, ControlConnection $controlConnection): Request
    {
        $request::setTrustedProxies([$controlConnection->socket->remoteAddress, '127.0.0.1'], Request::HEADER_X_FORWARDED_ALL);

        $request->headers->set('Host', $controlConnection->host);
        $request->headers->set('X-Forwarded-Proto', $request->isSecure() ? 'https' : 'http');
        $request->headers->set('X-Expose-Request-ID', uniqid());
        $request->headers->set('Upgrade-Insecure-Requests', true);
        $request->headers->set('X-Exposed-By', config('app.name') . ' '. config('app.version'));
        $request->headers->set('X-Original-Host', "{$controlConnection->subdomain}.{$this->configuration->hostname()}:{$this->configuration->port()}");

        return $request;
    }
}

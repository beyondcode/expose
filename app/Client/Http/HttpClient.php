<?php

namespace App\Client\Http;

use App\Client\Configuration;
use App\Client\Http\Modifiers\CheckBasicAuthentication;
use App\Logger\RequestLogger;
use Clue\React\Buzz\Browser;
use function GuzzleHttp\Psr7\parse_request;
use function GuzzleHttp\Psr7\str;
use Laminas\Http\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Frame;
use React\EventLoop\LoopInterface;
use React\Socket\Connector;
use React\Stream\ReadableStreamInterface;

class HttpClient
{
    /** @var LoopInterface */
    protected $loop;

    /** @var RequestLogger */
    protected $logger;

    /** @var Request */
    protected $request;

    protected $connectionData;

    /** @var array */
    protected $modifiers = [
        CheckBasicAuthentication::class,
    ];
    /** @var Configuration */
    protected $configuration;

    public function __construct(LoopInterface $loop, RequestLogger $logger, Configuration $configuration)
    {
        $this->loop = $loop;
        $this->logger = $logger;
        $this->configuration = $configuration;
    }

    public function performRequest(string $requestData, WebSocket $proxyConnection = null, $connectionData = null)
    {
        $this->connectionData = $connectionData;

        $this->request = $this->parseRequest($requestData);

        $this->logger->logRequest($requestData, $this->request);

        $request = $this->passRequestThroughModifiers(parse_request($requestData), $proxyConnection);

        transform($request, function ($request) use ($proxyConnection) {
            $this->sendRequestToApplication($request, $proxyConnection);
        });
    }

    protected function passRequestThroughModifiers(RequestInterface $request, ?WebSocket $proxyConnection = null): ?RequestInterface
    {
        foreach ($this->modifiers as $modifier) {
            $request = app($modifier)->handle($request, $proxyConnection);

            if (is_null($request)) {
                break;
            }
        }

        return $request;
    }

    protected function createConnector(): Connector
    {
        return new Connector($this->loop, [
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);
    }

    protected function sendRequestToApplication(RequestInterface $request, $proxyConnection = null)
    {
        (new Browser($this->loop, $this->createConnector()))
            ->withFollowRedirects(false)
            ->withRejectErrorResponse(false)
            ->requestStreaming($request->getMethod(), $this->getExposeUri($request), $request->getHeaders(), $request->getBody())
            ->then(function (ResponseInterface $response) use ($proxyConnection) {
                if (! isset($response->buffer)) {
                    //$response = $this->rewriteResponseHeaders($response);

                    $response->buffer = str($response);
                }

                $this->sendChunkToServer($response->buffer, $proxyConnection);

                /* @var $body ReadableStreamInterface */
                $body = $response->getBody();

                $this->logResponse(str($response));

                $body->on('data', function ($chunk) use ($proxyConnection, $response) {
                    $response->buffer .= $chunk;

                    $this->sendChunkToServer($chunk, $proxyConnection);
                });

                $body->on('close', function () use ($proxyConnection, $response) {
                    $this->logResponse($response->buffer);

                    optional($proxyConnection)->close();
                });
            });
    }

    protected function sendChunkToServer(string $chunk, ?WebSocket $proxyConnection = null)
    {
        transform($proxyConnection, function ($proxyConnection) use ($chunk) {
            $binaryMsg = new Frame($chunk, true, Frame::OP_BINARY);
            $proxyConnection->send($binaryMsg);
        });
    }

    protected function logResponse(string $rawResponse)
    {
        $this->logger->logResponse($this->request, $rawResponse);
    }

    protected function parseRequest($data): Request
    {
        return Request::fromString($data);
    }

    protected function rewriteResponseHeaders(ResponseInterface $response)
    {
        if (! $response->hasHeader('Location')) {
            return $response;
        }

        $location = $response->getHeaderLine('Location');

        if (! strstr($location, $this->connectionData->host)) {
            return $response;
        }

        $location = str_replace(
            $this->connectionData->host,
            $this->configuration->getUrl($this->connectionData->subdomain),
            $location
        );

        return $response->withHeader('Location', $location);
    }

    private function getExposeUri(RequestInterface $request): UriInterface
    {
        $exposeProto = $request->getHeader('x-expose-proto')[0];
        $exposeHost = explode(':', $request->getHeader('x-expose-host')[0]);

        return $request->getUri()
            ->withScheme($exposeProto)
            ->withHost($exposeHost[0])
            ->withPort($exposeHost[1]);
    }
}

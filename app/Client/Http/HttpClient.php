<?php

namespace App\Client\Http;

use App\Client\Http\Modifiers\CheckBasicAuthentication;
use App\Logger\RequestLogger;
use Clue\React\Buzz\Browser;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use Laminas\Http\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Frame;
use React\EventLoop\LoopInterface;
use React\Socket\Connector;
use function GuzzleHttp\Psr7\parse_request;
use function GuzzleHttp\Psr7\str;

class HttpClient
{
    /** @var LoopInterface */
    protected $loop;

    /** @var RequestLogger */
    protected $logger;

    /** @var Request */
    protected $request;

    /** @var array */
    protected $modifiers = [
        CheckBasicAuthentication::class,
    ];

    public function __construct(LoopInterface $loop, RequestLogger $logger)
    {
        $this->loop = $loop;
        $this->logger = $logger;
    }

    public function performRequest(string $requestData, WebSocket $proxyConnection = null, string $requestId = null)
    {
        $this->request = $this->parseRequest($requestData);

        $this->logger->logRequest($requestData, $this->request);

        $request = $this->passRequestThroughModifiers(parse_request($requestData), $proxyConnection);

        dump($this->request->getMethod() . ' ' . $this->request->getUri()->getPath());

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
        return new Connector($this->loop, array(
            'dns' => '127.0.0.1',
            'tls' => array(
                'verify_peer' => false,
                'verify_peer_name' => false
            )
        ));
    }

    protected function sendRequestToApplication(RequestInterface $request, $proxyConnection = null)
    {
        (new Browser($this->loop, $this->createConnector()))
            ->withOptions([
                'followRedirects' => false,
                'obeySuccessCode' => false,
                'streaming' => true,
            ])
            ->send($request)
            ->then(function (ResponseInterface $response) use ($proxyConnection) {
                if (! isset($response->buffer)) {
                    $response->buffer = str($response);
                }

                $this->sendChunkToServer($response->buffer, $proxyConnection);

                /* @var $body \React\Stream\ReadableStreamInterface */
                $body = $response->getBody();

                $this->logResponse(str($response));

                $body->on('data', function ($chunk) use ($proxyConnection, $response) {
                    $response->buffer .= $chunk;

                    $this->sendChunkToServer($chunk, $proxyConnection);

                    if ($chunk === "") {
                        $this->logResponse($response->buffer);

                        optional($proxyConnection)->close();
                    }
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
}

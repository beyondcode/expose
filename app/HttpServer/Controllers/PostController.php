<?php

namespace App\HttpServer\Controllers;

use App\HttpServer\QueryParameters;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use function GuzzleHttp\Psr7\parse_request;

abstract class PostController extends Controller
{
    protected $keepConnectionOpen = false;

    public function onOpen(ConnectionInterface $connection, RequestInterface $request = null)
    {
        dump(memory_get_usage(true));
        $connection->contentLength = $this->findContentLength($request->getHeaders());

        $connection->requestBuffer = (string) $request->getBody();

        $connection->request = $request;

        $this->checkContentLength($connection);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        if (! isset($from->requestBuffer)) {
            $request = parse_request($msg);
            $from->contentLength = $this->findContentLength($request->getHeaders());
            $from->request = $request;
            $from->requestBuffer = (string) $request->getBody();
        } else {
            $from->requestBuffer .= $msg;
        }

        $this->checkContentLength($from);
    }

    protected function findContentLength(array $headers): int
    {
        return Collection::make($headers)->first(function ($values, $header) {
                return strtolower($header) === 'content-length';
            })[0] ?? 0;
    }

    protected function checkContentLength(ConnectionInterface $connection)
    {
        if (strlen($connection->requestBuffer) === $connection->contentLength) {
            $laravelRequest = $this->createLaravelRequest($connection);

            $this->handle($laravelRequest, $connection);

            if (! $this->keepConnectionOpen) {
                $connection->close();
            }

            unset($connection->requestBuffer);
            unset($connection->contentLength);
            unset($connection->request);
        }
    }

    abstract public function handle(Request $request, ConnectionInterface $httpConnection);

    protected function createLaravelRequest(ConnectionInterface $connection): Request
    {
        $serverRequest = (new ServerRequest(
            $connection->request->getMethod(),
            $connection->request->getUri(),
            $connection->request->getHeaders(),
            $connection->requestBuffer,
            $connection->request->getProtocolVersion()
        ))->withQueryParams(QueryParameters::create($connection->request)->all());

        return Request::createFromBase((new HttpFoundationFactory)->createRequest($serverRequest));
    }
}

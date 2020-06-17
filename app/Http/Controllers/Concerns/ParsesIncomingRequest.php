<?php

namespace App\Http\Controllers\Concerns;

use App\Http\QueryParameters;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Ratchet\ConnectionInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;

trait ParsesIncomingRequest
{
    protected function findContentLength(array $headers): int
    {
        return Collection::make($headers)->first(function ($values, $header) {
            return strtolower($header) === 'content-length';
        })[0] ?? 0;
    }

    protected function shouldHandleRequest(Request $request, ConnectionInterface $httpConnection): bool
    {
        return true;
    }

    protected function checkContentLength(ConnectionInterface $connection)
    {
        if (strlen($connection->requestBuffer) === $connection->contentLength) {
            $connection->laravelRequest = $this->createLaravelRequest($connection);

            if ($this->shouldHandleRequest($connection->laravelRequest, $connection)) {
                $this->handle($connection->laravelRequest, $connection);
            }

            if (! $this->keepConnectionOpen) {
                $connection->close();
            }

            unset($connection->requestBuffer);
            unset($connection->contentLength);
            unset($connection->request);
        }
    }

    protected function createLaravelRequest(ConnectionInterface $connection): Request
    {
        try {
            parse_str($connection->requestBuffer, $bodyParameters);
        } catch (\Throwable $e) {
            $bodyParameters = [];
        }

        $serverRequest = (new ServerRequest(
            $connection->request->getMethod(),
            $connection->request->getUri(),
            $connection->request->getHeaders(),
            $connection->requestBuffer,
            $connection->request->getProtocolVersion(),
            [
                'REMOTE_ADDR' => $connection->remoteAddress,
            ]
        ))
            ->withQueryParams(QueryParameters::create($connection->request)->all())
            ->withParsedBody($bodyParameters);

        return Request::createFromBase((new HttpFoundationFactory)->createRequest($serverRequest));
    }
}

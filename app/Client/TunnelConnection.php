<?php

namespace App\Client;

use App\Logger\RequestLogger;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laminas\Http\Request;
use Laminas\Http\Response;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Stream\Util;
use function GuzzleHttp\Psr7\str;

class TunnelConnection
{
    /** @var LoopInterface */
    protected $loop;

    /** @var RequestLogger */
    protected $logger;

    /** @var Request */
    protected $request;

    public function __construct(LoopInterface $loop, RequestLogger $logger)
    {
        $this->loop = $loop;
        $this->logger = $logger;
    }

    protected function requiresAuthentication(): bool
    {
        return !empty($this->getCredentials());
    }

    public function performRequest($requestData, ConnectionInterface $proxyConnection = null)
    {
        $this->request = $this->parseRequest($requestData);

        $this->logger->logRequest($requestData, $this->request);

        dump($this->request->getMethod() . ' ' . $this->request->getUri()->getPath());

        if ($this->requiresAuthentication() && !is_null($proxyConnection)) {
            $username = $this->getAuthorizationUsername();
            if (is_null($username)) {
                $proxyConnection->write(
                    str(new \GuzzleHttp\Psr7\Response(401, [
                        'WWW-Authenticate' => 'Basic realm=Expose'
                    ], 'Unauthorized'))
                );
                $proxyConnection->end();
                return;
            }
        }

        (new Connector($this->loop))
            ->connect("localhost:80")
            ->then(function (ConnectionInterface $connection) use ($requestData, $proxyConnection) {
                $connection->on('data', function ($data) use (&$chunks, &$contentLength, $connection, $proxyConnection) {
                    if (!isset($connection->httpBuffer)) {
                        $connection->httpBuffer = '';
                    }

                    $connection->httpBuffer .= $data;

                    $response = $this->parseResponse($connection->httpBuffer);

                    if (! is_null($response) && $this->hasBufferedAllData($connection)) {

                        $this->logger->logResponse($this->request, $connection->httpBuffer, $response);

                        if (! is_null($proxyConnection)) {
                            $proxyConnection->write($connection->httpBuffer);
                        }

                        unset($proxyConnection->buffer);

                        unset($connection->httpBuffer);
                    }

                });
                $connection->write($requestData);
            });
    }

    protected function getContentLength($connection): ?int
    {
        $response = $this->parseResponse($connection->httpBuffer);

        return Arr::get($response->getHeaders()->toArray(), 'Content-Length');
    }

    protected function hasBufferedAllData($connection)
    {
        return is_null($this->getContentLength($connection)) || strlen(Str::after($connection->httpBuffer, "\r\n\r\n")) === $this->getContentLength($connection);
    }

    protected function parseResponse(string $response)
    {
        try {
            return Response::fromString($response);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function parseRequest($data): Request
    {
        return Request::fromString($data);
    }

    protected function getCredentials()
    {
        try {
            $credentials = explode(':', $GLOBALS['expose.auth']);
            return [
                $credentials[0] => $credentials[1],
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getAuthorizationUsername(): ?string
    {
        $authorization = $this->parseAuthorizationHeader(Arr::get($this->request->getHeaders()->toArray(), 'Authorization', ''));
        $credentials = $this->getCredentials();

        if (empty($authorization)) {
            return null;
        }

        if (!array_key_exists($authorization['username'], $credentials)) {
            return null;
        }

        if ($credentials[$authorization['username']] !== $authorization['password']) {
            return null;
        }

        return $authorization['username'];
    }

    protected function parseAuthorizationHeader(string $header)
    {
        if (strpos($header, 'Basic') !== 0) {
            return null;
        }

        $header = base64_decode(substr($header, 6));

        if ($header === false) {
            return null;
        }

        $header = explode(':', $header, 2);

        return [
            'username' => $header[0],
            'password' => isset($header[1]) ? $header[1] : null,
        ];
    }
}

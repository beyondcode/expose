<?php

namespace App\Logger;

use Laminas\Http\Request;
use Laminas\Http\Response;
use React\Http\Browser;

class RequestLogger
{
    /** @var array */
    protected $requests = [];

    /** @var Browser */
    protected $client;

    /** @var CliRequestLogger */
    protected $cliRequestLogger;

    public function __construct(Browser $browser, CliRequestLogger $cliRequestLogger)
    {
        $this->client = $browser;
        $this->cliRequestLogger = $cliRequestLogger;
    }

    public function findLoggedRequest(string $id): ?LoggedRequest
    {
        return collect($this->requests)->first(function (LoggedRequest $loggedRequest) use ($id) {
            return $loggedRequest->id() === $id;
        });
    }

    public function logRequest(string $rawRequest, Request $request): LoggedRequest
    {
        $loggedRequest = new LoggedRequest($rawRequest, $request);

        array_unshift($this->requests, $loggedRequest);

        $this->requests = array_slice($this->requests, 0, config('expose.max_logged_requests', 10));

        $this->cliRequestLogger->logRequest($loggedRequest);

        $this->pushLoggedRequest($loggedRequest);

        return $loggedRequest;
    }

    public function logResponse(Request $request, string $rawResponse)
    {
        $this->requests = collect($this->requests)->transform(function (LoggedRequest $loggedRequest) use ($request, $rawResponse) {
            if ($loggedRequest->getRequest() !== $request) {
                return $loggedRequest;
            }

            $loggedRequest->setResponse($rawResponse, Response::fromString($rawResponse));
            $this->cliRequestLogger->logRequest($loggedRequest);
            $this->pushLoggedRequest($loggedRequest);

            return $loggedRequest;
        })->toArray();
    }

    public function getData(): array
    {
        return $this->requests;
    }

    public function clear()
    {
        $this->requests = [];
    }

    public function pushLoggedRequest(LoggedRequest $request)
    {
        $this
            ->client
            ->post(
                'http://127.0.0.1:4040/api/logs',
                ['Content-Type' => 'application/json'],
                json_encode($request, JSON_INVALID_UTF8_IGNORE)
            );
    }
}

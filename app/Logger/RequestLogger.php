<?php

namespace App\Logger;

use Clue\React\Buzz\Browser;
use GuzzleHttp\RequestOptions;
use Laminas\Http\Request;
use Laminas\Http\Response;
use function GuzzleHttp\Psr7\stream_for;

class RequestLogger
{
    /** @var array */
    protected $requests = [];

    /** @var array */
    protected $responses = [];

    /** @var CliRequestLogger */
    protected $cliRequestLogger;

    const MAX_LOGGED_REQUESTS = 10;

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

        $this->requests = array_slice($this->requests, 0, static::MAX_LOGGED_REQUESTS);

        $this->cliRequestLogger->logRequest($loggedRequest);

        $this->pushLogs();

        return $loggedRequest;
    }

    public function logResponse(Request $request, string $rawResponse)
    {
        $loggedRequest = collect($this->requests)->first(function (LoggedRequest $loggedRequest) use ($request) {
            return $loggedRequest->getRequest() === $request;
        });
        if ($loggedRequest) {
            $loggedRequest->setResponse($rawResponse, Response::fromString($rawResponse));

            $this->cliRequestLogger->logRequest($loggedRequest);

            $this->pushLogs();
        }
    }

    public function getData(): array
    {
        return $this->requests;
    }

    public function clear()
    {
        $this->requests = [];

        $this->pushLogs();
    }

    public function pushLogs()
    {
        // TODO: Make dashboard part configurable
        $this
            ->client
            ->post(
                'http://127.0.0.1:4040/api/logs',
                ['Content-Type' => 'application/json'],
                json_encode($this->getData(), JSON_INVALID_UTF8_IGNORE)
            );
    }
}

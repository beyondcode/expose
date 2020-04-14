<?php

namespace App\Logger;

use Clue\React\Buzz\Browser;
use GuzzleHttp\RequestOptions;
use Laminas\Http\Request;
use Laminas\Http\Response;
use function GuzzleHttp\Psr7\stream_for;

class RequestLogger
{
    protected $requests = [];
    protected $responses = [];

    public function __construct(Browser $browser)
    {
        $this->client = $browser;
    }

    public function findLoggedRequest(string $id): ?LoggedRequest
    {
        return collect($this->requests)->first(function (LoggedRequest $loggedRequest) use ($id) {
            return $loggedRequest->id() === $id;
        });
    }

    public function logRequest(string $rawRequest, Request $request)
    {
        array_unshift($this->requests, new LoggedRequest($rawRequest, $request));

        $this->requests = array_slice($this->requests, 0, 10);

        $this->pushLogs();
    }

    public function logResponse(Request $request, string $rawResponse, Response $response)
    {
        $loggedRequest = collect($this->requests)->first(function (LoggedRequest $loggedRequest) use ($request) {
            return $loggedRequest->getRequest() === $request;
        });
        if ($loggedRequest) {
            $loggedRequest->setResponse($rawResponse, $response);

            $this->pushLogs();
        }
    }

    public function getData()
    {
        return $this->requests;
    }

    protected function pushLogs()
    {
        $this
            ->client
            ->post(
                'http://127.0.0.1:4040/logs',
                ['Content-Type' => 'application/json'],
                json_encode($this->getData(), JSON_INVALID_UTF8_IGNORE)
            );
    }
}

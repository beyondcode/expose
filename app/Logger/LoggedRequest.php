<?php

namespace App\Logger;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Namshi\Cuzzle\Formatter\CurlFormatter;
use Riverline\MultiPartParser\StreamedPart;
use function GuzzleHttp\Psr7\parse_request;

class LoggedRequest implements \JsonSerializable
{
    /** @var string */
    protected $rawRequest;

    /** @var Request */
    protected $parsedRequest;

    /** @var string */
    protected $rawResponse;

    /** @var Response */
    protected $parsedResponse;

    /** @var string */
    protected $id;

    /** @var Carbon */
    protected $startTime;

    /** @var Carbon */
    protected $stopTime;

    /** @var string */
    protected $subdomain;

    /** @var array */
    protected $additionalData = [];

    public function __construct(string $rawRequest, Request $parsedRequest)
    {
        $this->startTime = now();
        $this->rawRequest = $rawRequest;
        $this->parsedRequest = $parsedRequest;
        $this->id = $this->getRequestId();
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        $data = [
            'id' => $this->id,
            'performed_at' => $this->startTime->toDateTimeString(),
            'duration' => $this->startTime->diffInMilliseconds($this->stopTime, false),
            'subdomain' => $this->detectSubdomain(),
            'request' => [
                'raw' => $this->isBinary($this->rawRequest) ? 'BINARY' : $this->rawRequest,
                'method' => $this->parsedRequest->getMethod(),
                'uri' => $this->parsedRequest->getUri()->getPath(),
                'headers' => $this->parsedRequest->getHeaders()->toArray(),
                'body' => $this->isBinary($this->rawRequest) ? 'BINARY' : $this->parsedRequest->getContent(),
                'query' => $this->parsedRequest->getQuery()->toArray(),
                'post' => $this->getPost(),
                'curl' => '', //(new CurlFormatter())->format(parse_request($this->rawRequest)),
                'additional_data' => $this->additionalData,
            ],
        ];

        if ($this->parsedResponse) {
            try {
                $body = $this->parsedResponse->getBody();
            } catch (\Exception $e) {
                $body = '';
            }
            $data['response'] = [
                'raw' => $this->shouldReturnBody() ? $this->rawResponse : 'BINARY',
                'status' => $this->parsedResponse->getStatusCode(),
                'headers' => $this->parsedResponse->getHeaders()->toArray(),
                'reason' => $this->parsedResponse->getReasonPhrase(),
                'body' => $this->shouldReturnBody() ? $body : 'BINARY',
            ];
        }

        return $data;
    }

    public function setAdditionalData(array $data)
    {
        $this->additionalData = array_merge($this->additionalData, $data);
    }

    protected function isBinary(string $string): bool
    {
        return preg_match('~[^\x20-\x7E\t\r\n]~', $string) > 0;
    }

    protected function shouldReturnBody()
    {
        $contentType = Arr::get($this->parsedResponse->getHeaders()->toArray(), 'Content-Type');

        return $contentType === 'application/json' || Str::is('text/*', $contentType) || Str::is('*javascript*', $contentType);
    }

    public function getRequest()
    {
        return $this->parsedRequest;
    }

    public function setResponse(string $rawResponse, Response $response)
    {
        $this->parsedResponse = $response;

        $this->rawResponse = $rawResponse;

        if (is_null($this->stopTime)) {
            $this->stopTime = now();
        }
    }

    public function id()
    {
        return $this->id;
    }

    public function getRequestData()
    {
        return $this->rawRequest;
    }

    protected function getResponseBody()
    {
        return \Laminas\Http\Response::fromString($this->rawResponse)->getBody();
    }

    protected function getPost()
    {
        $postData = [];

        $contentType = Arr::get($this->parsedRequest->getHeaders()->toArray(), 'Content-Type');

        switch ($contentType) {
            case 'application/x-www-form-urlencoded':
                parse_str($this->parsedRequest->getContent(), $postData);
                $postData = collect($postData)->map(function ($key, $value) {
                    return [
                        'name' => $key,
                        'value' => $value,
                    ];
                })->toArray();
                break;
            case 'application/json':
                $postData = collect(json_decode($this->parsedRequest->getContent(), true))->map(function ($key, $value) {
                    return [
                        'name' => $key,
                        'value' => $value,
                    ];
                })->toArray();

                break;
            default:
                $stream = fopen('php://temp', 'rw');
                fwrite($stream, $this->rawRequest);
                rewind($stream);

                try {
                    $document = new StreamedPart($stream);
                    if ($document->isMultiPart()) {
                        $postData = collect($document->getParts())->map(function (StreamedPart $part) {
                            return [
                                'name' => $part->getName(),
                                'value' => $part->isFile() ? null : $part->getBody(),
                                'is_file' => $part->isFile(),
                                'filename' => $part->isFile() ? $part->getFileName() : null,
                                'mime_type' => $part->isFile() ? $part->getMimeType() : null,
                            ];
                        })->toArray();
                    }
                } catch (\Exception $e) {
                    //
                }
                break;
        }

        return $postData;
    }

    protected function detectSubdomain()
    {
        return Arr::get($this->parsedRequest->getHeaders()->toArray(), 'X-Original-Host');
    }

    protected function getRequestId()
    {
        return Arr::get($this->parsedRequest->getHeaders()->toArray(), 'X-Expose-Request-ID', (string)Str::uuid());
    }
}

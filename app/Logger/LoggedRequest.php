<?php

namespace App\Logger;

use Carbon\Carbon;
use function GuzzleHttp\Psr7\parse_request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Namshi\Cuzzle\Formatter\CurlFormatter;
use Riverline\MultiPartParser\StreamedPart;

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
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        $data = [
            'id' => $this->id,
            'performed_at' => $this->startTime->toDateTimeString(),
            'duration' => $this->getDuration(),
            'subdomain' => $this->detectSubdomain(),
            'request' => [
                'raw' => $this->isBinary($this->rawRequest) ? 'BINARY' : $this->rawRequest,
                'method' => $this->parsedRequest->getMethod(),
                'uri' => $this->parsedRequest->getUriString(),
                'headers' => $this->parsedRequest->getHeaders()->toArray(),
                'body' => $this->isBinary($this->rawRequest) ? 'BINARY' : $this->parsedRequest->getContent(),
                'query' => $this->parsedRequest->getQuery()->toArray(),
                'post' => $this->getPostData(),
                'curl' => $this->getRequestAsCurl(),
                'additional_data' => $this->additionalData,
            ],
        ];

        if ($this->parsedResponse) {
            $logBody = $this->shouldReturnBody();

            try {
                $body = $logBody ? $this->parsedResponse->getBody() : '';
            } catch (\Exception $e) {
                $body = '';
            }

            $data['response'] = [
                'raw' => $logBody ? $this->rawResponse : 'SKIPPED BY CONFIG OR BINARY RESPONSE',
                'status' => $this->parsedResponse->getStatusCode(),
                'headers' => $this->parsedResponse->getHeaders()->toArray(),
                'reason' => $this->parsedResponse->getReasonPhrase(),
                'body' => $logBody ? $body : 'SKIPPED BY CONFIG OR BINARY RESPONSE',
            ];
        }

        return $data;
    }

    public function setAdditionalData(array $data)
    {
        $this->additionalData = array_merge($this->additionalData, $data);
    }

    public function getAdditionalData(): array
    {
        return $this->additionalData;
    }

    protected function isBinary(string $string): bool
    {
        return preg_match('~[^\x20-\x7E\t\r\n]~', $string) > 0;
    }

    protected function shouldReturnBody(): bool
    {
        if ($this->skipByStatus()) {
            return false;
        }

        if ($this->skipByContentType()) {
            return false;
        }

        if ($this->skipByExtension()) {
            return false;
        }

        if ($this->skipBySize()) {
            return false;
        }

        $header = $this->parsedResponse->getHeaders()->get('Content-Type');
        $contentType = $header ? $header->getMediaType() : '';
        $patterns = [
            'application/json',
            'text/*',
            '*javascript*',
        ];

        return Str::is($patterns, $contentType);
    }

    protected function skipByStatus(): bool
    {
        if (empty(config()->get('expose.skip_body_log.status'))) {
            return false;
        }

        return Str::is(config()->get('expose.skip_body_log.status'), $this->parsedResponse->getStatusCode());
    }

    protected function skipByContentType(): bool
    {
        if (empty(config()->get('expose.skip_body_log.content_type'))) {
            return false;
        }

        $header = $this->parsedResponse->getHeaders()->get('Content-Type');
        $contentType = $header ? $header->getMediaType() : '';

        return Str::is(config()->get('expose.skip_body_log.content_type'), $contentType);
    }

    protected function skipByExtension(): bool
    {
        if (empty(config()->get('expose.skip_body_log.extension'))) {
            return false;
        }

        return Str::is(config()->get('expose.skip_body_log.extension'), $this->parsedRequest->getUri()->getPath());
    }

    protected function skipBySize(): bool
    {
        $configSize = $this->getConfigSize(config()->get('expose.skip_body_log.size', '1MB'));
        $contentLength = $this->parsedResponse->getHeaders()->get('Content-Length');

        if (! $contentLength) {
            return false;
        }

        $contentSize = $contentLength->getFieldValue() ?? 0;

        return $contentSize > $configSize;
    }

    protected function getConfigSize(string $size): int
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $number = substr($size, 0, -2);
        $suffix = strtoupper(substr($size, -2));

        // B or no suffix
        if (is_numeric(substr($suffix, 0, 1))) {
            return preg_replace('/[^\d]/', '', $size);
        }

        // if we have an error in the input, default to GB
        $exponent = array_flip($units)[$suffix] ?? 5;

        return $number * (1024 ** $exponent);
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

    public function id(): string
    {
        return $this->id;
    }

    public function getRequestData(): ?string
    {
        return $this->rawRequest;
    }

    public function getResponse(): ?Response
    {
        return $this->parsedResponse;
    }

    public function getPostData()
    {
        $postData = [];

        $contentType = Arr::get($this->parsedRequest->getHeaders()->toArray(), 'Content-Type');

        switch ($contentType) {
            case 'application/x-www-form-urlencoded':
                parse_str($this->parsedRequest->getContent(), $postData);
                $postData = collect($postData)->map(function ($value, $key) {
                    return [
                        'name' => $key,
                        'value' => $value,
                    ];
                })->toArray();
                break;
            case 'application/json':
                $postData = collect(json_decode($this->parsedRequest->getContent(), true))->map(function ($value, $key) {
                    return [
                        'name' => $key,
                        'value' => $value,
                    ];
                })->values()->toArray();

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
        return collect($this->parsedRequest->getHeaders()->toArray())
            ->mapWithKeys(function ($value, $key) {
                return [strtolower($key) => $value];
            })->get('x-original-host');
    }

    protected function getRequestId()
    {
        return collect($this->parsedRequest->getHeaders()->toArray())
            ->mapWithKeys(function ($value, $key) {
                return [strtolower($key) => $value];
            })->get('x-expose-request-id', (string) Str::uuid());
    }

    public function getDuration()
    {
        return $this->startTime->diffInMilliseconds($this->stopTime, false);
    }

    protected function getRequestAsCurl(): string
    {
        try {
            return (new CurlFormatter())->format(parse_request($this->rawRequest));
        } catch (\Throwable $e) {
            return '';
        }
    }
}

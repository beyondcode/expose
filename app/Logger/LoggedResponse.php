<?php

namespace App\Logger;

use Illuminate\Support\Str;
use Laminas\Http\Request;
use Laminas\Http\Response;

class LoggedResponse
{
    /** @var string */
    protected $rawResponse;

    /** @var Response */
    protected $response;

    /** @var Request */
    protected $request;

    protected $reasonPhrase;
    protected $body;
    protected $statusCode;
    protected $headers;

    public function __construct(string $rawResponse, Response $response, Request $request)
    {
        $this->rawResponse = $rawResponse;
        $this->response = $response;
        $this->request = $request;

        if (! $this->shouldReturnBody()) {
            $this->rawResponse = 'SKIPPED BY CONFIG OR BINARY RESPONSE';
            $this->body = 'SKIPPED BY CONFIG OR BINARY RESPONSE';
        } else {
            try {
                $this->body = $response->getBody();
            } catch (\Exception $e) {
                $this->body = '';
            }
        }

        $this->statusCode = $response->getStatusCode();
        $this->reasonPhrase = $response->getReasonPhrase();
        $this->headers = $response->getHeaders()->toArray();

        $this->response = null;
        $this->request = null;
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

        $header = $this->response->getHeaders()->get('Content-Type');
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

        return Str::is(config()->get('expose.skip_body_log.status'), $this->response->getStatusCode());
    }

    protected function skipByContentType(): bool
    {
        if (empty(config()->get('expose.skip_body_log.content_type'))) {
            return false;
        }

        $header = $this->response->getHeaders()->get('Content-Type');
        $contentType = $header ? $header->getMediaType() : '';

        return Str::is(config()->get('expose.skip_body_log.content_type'), $contentType);
    }

    protected function skipByExtension(): bool
    {
        if (empty(config()->get('expose.skip_body_log.extension'))) {
            return false;
        }

        return Str::is(config()->get('expose.skip_body_log.extension'), $this->request->getUri()->getPath());
    }

    protected function skipBySize(): bool
    {
        $configSize = $this->getConfigSize(config()->get('expose.skip_body_log.size', '1MB'));
        $contentLength = $this->response->getHeaders()->get('Content-Length');

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

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    public function toArray()
    {
        return [
            'raw' => $this->rawResponse,
            'status' => $this->statusCode,
            'headers' => $this->headers,
            'reason' => $this->reasonPhrase,
            'body' => $this->body,
        ];
    }
}

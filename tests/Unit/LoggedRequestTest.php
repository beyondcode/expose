<?php

namespace Tests\Unit;

use App\Logger\LoggedRequest;
use GuzzleHttp\Psr7\Request;
use function GuzzleHttp\Psr7\str;
use Laminas\Http\Request as LaminasRequest;
use Tests\TestCase;

class LoggedRequestTest extends TestCase
{
    /** @test */
    public function it_retrieves_the_request_id()
    {
        $rawRequest = str(new Request(200, '/expose', [
            'X-Expose-Request-ID' => 'example-request',
        ]));
        $parsedRequest = LaminasRequest::fromString($rawRequest);

        $loggedRequest = new LoggedRequest($rawRequest, $parsedRequest);
        $this->assertSame('example-request', $loggedRequest->id());
    }

    /** @test */
    public function it_returns_post_data_for_json_payloads()
    {
        $postData = [
            'name' => 'Marcel',
            'project' => 'expose',
        ];

        $rawRequest = str(new Request(200, '/expose', [
            'Content-Type' => 'application/json',
        ], json_encode($postData)));
        $parsedRequest = LaminasRequest::fromString($rawRequest);

        $loggedRequest = new LoggedRequest($rawRequest, $parsedRequest);

        $this->assertSame([
            [
                'name' => 'name',
                'value' => 'Marcel',
            ],
            [
                'name' => 'project',
                'value' => 'expose',
            ],
        ], $loggedRequest->getPostData());
    }

    /** @test */
    public function it_returns_the_raw_request()
    {
        $rawRequest = str(new Request(200, '/expose', [
            'X-Expose-Request-ID' => 'example-request',
        ]));
        $parsedRequest = LaminasRequest::fromString($rawRequest);

        $loggedRequest = new LoggedRequest($rawRequest, $parsedRequest);
        $this->assertSame($rawRequest, $loggedRequest->getRequestData());
    }

    /** @test */
    public function it_returns_the_parsed_request()
    {
        $rawRequest = str(new Request(200, '/expose', [
            'X-Expose-Request-ID' => 'example-request',
        ]));
        $parsedRequest = LaminasRequest::fromString($rawRequest);

        $loggedRequest = new LoggedRequest($rawRequest, $parsedRequest);
        $this->assertSame($parsedRequest, $loggedRequest->getRequest());
    }
}

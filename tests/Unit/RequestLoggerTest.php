<?php

namespace Tests\Unit;

use App\Logger\CliRequestLogger;
use App\Logger\RequestLogger;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Laminas\Http\Request as LaminasRequest;
use Mockery as m;
use React\Http\Browser;
use Tests\TestCase;

use function GuzzleHttp\Psr7\str;

class RequestLoggerTest extends TestCase
{
    /** @test */
    public function it_can_log_requests()
    {
        $browser = m::mock(Browser::class);
        $browser->shouldReceive('post')
            ->once();

        $cliLogger = m::mock(CliRequestLogger::class);
        $cliLogger->shouldReceive('logRequest')->once();

        $requestString = str(new Request('GET', '/example'));
        $parsedRequest = LaminasRequest::fromString($requestString);

        $logger = new RequestLogger($browser, $cliLogger);
        $logger->logRequest($requestString, $parsedRequest);

        $this->assertCount(1, $logger->getData());
    }

    /** @test */
    public function it_can_clear_the_requests()
    {
        $browser = m::mock(Browser::class);
        $browser->shouldReceive('post')->once();

        $cliLogger = m::mock(CliRequestLogger::class);
        $cliLogger->shouldReceive('logRequest')->once();

        $requestString = str(new Request('GET', '/example'));
        $parsedRequest = LaminasRequest::fromString($requestString);

        $logger = new RequestLogger($browser, $cliLogger);
        $logger->logRequest($requestString, $parsedRequest);

        $logger->clear();

        $this->assertCount(0, $logger->getData());
    }

    /** @test */
    public function it_can_associate_a_response_with_a_request()
    {
        $browser = m::mock(Browser::class);
        $browser->shouldReceive('post')
            ->twice();

        $cliLogger = m::mock(CliRequestLogger::class);
        $cliLogger->shouldReceive('logRequest')
            ->twice();

        $requestString = str(new Request('GET', '/example'));
        $parsedRequest = LaminasRequest::fromString($requestString);

        $logger = new RequestLogger($browser, $cliLogger);
        $loggedRequest = $logger->logRequest($requestString, $parsedRequest);

        $this->assertNull($logger->findLoggedRequest($loggedRequest->id())->getResponse());

        $responseString = str(new Response(200, [], 'Hello World!'));

        $logger->logResponse($parsedRequest, $responseString);

        $this->assertNotNull($logger->findLoggedRequest($loggedRequest->id())->getResponse());
    }

    /** @test */
    public function it_can_find_a_request_by_id()
    {
        $browser = m::mock(Browser::class);
        $browser->shouldReceive('post')
            ->once();

        $cliLogger = m::mock(CliRequestLogger::class);
        $cliLogger->shouldReceive('logRequest')->once();

        $requestString = str(new Request('GET', '/example'));
        $parsedRequest = LaminasRequest::fromString($requestString);

        $logger = new RequestLogger($browser, $cliLogger);
        $loggedRequest = $logger->logRequest($requestString, $parsedRequest);

        $this->assertSame($loggedRequest, $logger->findLoggedRequest($loggedRequest->id()));
    }

    /** @test */
    public function it_only_stores_a_limited_amount_of_requests()
    {
        $browser = m::mock(Browser::class);
        $browser->shouldReceive('post');

        $cliLogger = m::mock(CliRequestLogger::class);
        $cliLogger->shouldReceive('logRequest');

        $requestString = str(new Request('GET', '/example'));
        $parsedRequest = LaminasRequest::fromString($requestString);

        $logger = new RequestLogger($browser, $cliLogger);

        foreach (range(1, 50) as $i) {
            $logger->logRequest($requestString, $parsedRequest);
        }

        $this->assertCount(config('expose.max_logged_requests'), $logger->getData());
    }
}

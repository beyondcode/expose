<?php

namespace Tests\Feature\Client;

use App\Client\Client;
use App\Client\Configuration;
use App\Client\Factory;
use App\Logger\RequestLogger;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use Tests\Feature\TestCase;

class ApiTest extends TestCase
{
    /** @var Browser */
    protected $browser;

    /** @var Factory */
    protected $dashboardFactory;

    /** @var RequestLogger */
    protected $requestLogger;

    public function setUp(): void
    {
        parent::setUp();

        $this->browser = new Browser($this->loop);
        $this->requestLogger = $this->app->make(RequestLogger::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->dashboardFactory->getApp()->close();
    }

    /** @test */
    public function accessing_the_available_tunnels_works()
    {
        $this->startDashboard();

        /** @var ResponseInterface $response */
        $response = $this->await($this->browser->get('http://127.0.0.1:4040/api/tunnels'));

        $this->assertSame(200, $response->getStatusCode());
    }

    /** @test */
    public function it_returns_the_connected_subdomain_urls()
    {
        Client::$subdomains = [
            'https://test.eu-1.sharedwithexpose.com',
        ];

        $this->startDashboard();

        /** @var ResponseInterface $response */
        $response = $this->await($this->browser->get('http://127.0.0.1:4040/api/tunnels'));

        $json = json_decode($response->getBody()->getContents());

        $this->assertIsArray($json->tunnels);
        $this->assertCount(1, $json->tunnels);
        $this->assertSame('https://test.eu-1.sharedwithexpose.com', $json->tunnels[0]);
    }

    protected function startDashboard()
    {
        app()->singleton(Configuration::class, function ($app) {
            return new Configuration('localhost', '8080', false);
        });

        $this->dashboardFactory = (new Factory())
            ->setLoop($this->loop)
            ->createHttpServer();
    }
}

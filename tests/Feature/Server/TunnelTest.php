<?php

namespace Tests\Feature\Server;

use App\Client\Client;
use App\Contracts\ConnectionManager;
use App\Server\Factory;
use Clue\React\Buzz\Browser;
use Clue\React\Buzz\Message\ResponseException;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use Ratchet\Client\WebSocket;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use React\Http\Server;
use Tests\Feature\TestCase;
use function Ratchet\Client\connect;

class TunnelTest extends TestCase
{
    /** @var Browser */
    protected $browser;

    /** @var Factory */
    protected $serverFactory;

    /** @var \React\Socket\Server */
    protected $testHttpServer;

    public function setUp(): void
    {
        parent::setUp();

        $this->browser = new Browser($this->loop);

        $this->startServer();
    }

    public function tearDown(): void
    {
        $this->serverFactory->getSocket()->close();

        if (isset($this->testHttpServer)) {
            $this->testHttpServer->close();
        }

        parent::tearDown();
    }

    /** @test */
    public function it_returns_404_for_non_existing_clients()
    {
        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage(404);

        $response = $this->await($this->browser->get('http://127.0.0.1:8080/', [
            'Host' => 'tunnel.localhost'
        ]));
    }

    /** @test */
    public function it_sends_incoming_requests_to_the_connected_client()
    {
        $this->createTestHttpServer();

        /**
         * We create an expose client that connects to our server and shares
         * the created test HTTP server
         */
        $client = $this->createClient();
        $this->await($client->connectToServer('127.0.0.1:8085', 'tunnel'));

        /**
         * Once the client is connected, we perform a GET request on the
         * created tunnel.
         */
        $response = $this->await($this->browser->get('http://127.0.0.1:8080/', [
            'Host' => 'tunnel.localhost'
        ]));

        $this->assertSame('Hello World!', $response->getBody()->getContents());
    }

    /** @test */
    public function it_rejects_clients_with_invalid_auth_tokens()
    {
        $this->app['config']['expose.admin.validate_auth_tokens'] = true;

        $this->createTestHttpServer();

        $this->expectException(\UnexpectedValueException::class);

        /**
         * We create an expose client that connects to our server and shares
         * the created test HTTP server
         */
        $client = $this->createClient();
        $result = $this->await($client->connectToServer('127.0.0.1:8085', 'tunnel'));
    }

    /** @test */
    public function it_allows_clients_with_valid_auth_tokens()
    {
        $this->app['config']['expose.admin.validate_auth_tokens'] = true;

        $this->createTestHttpServer();

        $this->expectException(\UnexpectedValueException::class);

        /**
         * We create an expose client that connects to our server and shares
         * the created test HTTP server
         */
        $client = $this->createClient();
        $this->await($client->connectToServer('127.0.0.1:8085', 'tunnel'));
    }

    protected function startServer()
    {
        $this->app['config']['expose.admin.database'] = ':memory:';

        $this->serverFactory = new Factory();

        $this->serverFactory->setLoop($this->loop)
            ->setHost('127.0.0.1')
            ->setHostname('localhost')
            ->createServer();
    }

    protected function createClient()
    {
        (new \App\Client\Factory())
            ->setLoop($this->loop)
            ->setHost('127.0.0.1')
            ->setPort(8080)
            ->createClient();

        return app(Client::class);
    }

    protected function createTestHttpServer()
    {
        $server = new Server(function (ServerRequestInterface $request) {
            return new Response(200, ['Content-Type' => 'text/plain'], "Hello World!");
        });

        $this->testHttpServer = new \React\Socket\Server(8085, $this->loop);
        $server->listen($this->testHttpServer);
    }
}

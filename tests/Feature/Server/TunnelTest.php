<?php

namespace Tests\Feature\Server;

use App\Client\Client;
use App\Server\Factory;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Browser;
use React\Http\Message\ResponseException;
use React\Http\Server;
use React\Socket\Connection;
use Tests\Feature\TestCase;

class TunnelTest extends TestCase
{
    /** @var Browser */
    protected $browser;

    /** @var Factory */
    protected $serverFactory;

    /** @var \React\Socket\Server */
    protected $testHttpServer;

    /** @var \React\Socket\Server */
    protected $testTcpServer;

    public function setUp(): void
    {
        parent::setUp();

        $this->browser = new Browser($this->loop);
        $this->browser = $this->browser->withFollowRedirects(false);

        $this->startServer();
    }

    public function tearDown(): void
    {
        $this->serverFactory->getSocket()->close();

        if (isset($this->testHttpServer)) {
            $this->testHttpServer->close();
        }

        if (isset($this->testTcpServer)) {
            $this->testTcpServer->close();
        }

        $this->await(\React\Promise\Timer\resolve(0.2, $this->loop));

        parent::tearDown();
    }

    /** @test */
    public function it_returns_404_for_non_existing_clients()
    {
        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage(404);

        $this->await($this->browser->get('http://127.0.0.1:8080/', [
            'Host' => 'tunnel.localhost',
        ]));
    }

    /** @test */
    public function it_returns_404_for_non_existing_clients_on_custom_hosts()
    {
        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage(404);

        $this->await($this->browser->get('http://127.0.0.1:8080/', [
            'Host' => 'tunnel.share.beyondco.de',
        ]));
    }

    /** @test */
    public function it_sends_incoming_requests_to_the_connected_client()
    {
        $this->app['config']['expose.admin.validate_auth_tokens'] = false;

        $this->createTestHttpServer();

        $this->app['config']['expose.admin.validate_auth_tokens'] = false;

        /**
         * We create an expose client that connects to our server and shares
         * the created test HTTP server.
         */
        $client = $this->createClient();
        $this->await($client->connectToServer('127.0.0.1:8085', 'tunnel'));

        /**
         * Once the client is connected, we perform a GET request on the
         * created tunnel.
         */
        $response = $this->await($this->browser->get('http://127.0.0.1:8080/', [
            'Host' => 'tunnel.localhost',
        ]));

        $this->assertSame('Hello World!', $response->getBody()->getContents());
    }

    /** @test */
    public function it_sends_incoming_requests_to_the_connected_client_on_custom_hosts()
    {
        $this->app['config']['expose.admin.validate_auth_tokens'] = false;

        $this->createTestHttpServer();

        $this->app['config']['expose.admin.validate_auth_tokens'] = false;

        /**
         * We create an expose client that connects to our server and shares
         * the created test HTTP server.
         */
        $client = $this->createClient();
        $this->await($client->connectToServer('127.0.0.1:8085', 'tunnel', 'share.beyondco.de'));

        /**
         * Once the client is connected, we perform a GET request on the
         * created tunnel.
         */
        $response = $this->await($this->browser->get('http://127.0.0.1:8080/', [
            'Host' => 'tunnel.share.beyondco.de',
        ]));

        $this->assertSame('Hello World!', $response->getBody()->getContents());
    }

    /** @test */
    public function it_sends_incoming_requests_to_the_connected_client_via_tcp()
    {
        $this->createTestTcpServer();

        $this->app['config']['expose.admin.validate_auth_tokens'] = false;

        /**
         * We create an expose client that connects to our server and shares
         * the created test HTTP server.
         */
        $client = $this->createClient();
        $response = $this->await($client->connectToServerAndShareTcp(8085));

        /**
         * Once the client is connected, we connect to the
         * created tunnel.
         */
        $connector = new \React\Socket\Connector($this->loop);
        $connection = $this->await($connector->connect('127.0.0.1:'.$response->shared_port));

        $this->assertInstanceOf(Connection::class, $connection);
    }

    /** @test */
    public function it_rejects_tcp_sharing_if_forbidden()
    {
        $this->createTestTcpServer();

        $this->app['config']['expose.admin.validate_auth_tokens'] = true;

        $user = $this->createUser([
            'name' => 'Marcel',
            'can_share_tcp_ports' => 0,
        ]);

        $this->expectException(\UnexpectedValueException::class);

        /**
         * We create an expose client that connects to our server and shares
         * the created test HTTP server.
         */
        $client = $this->createClient();
        $this->await($client->connectToServerAndShareTcp(8085, $user->auth_token));
    }

    /** @test */
    public function it_allows_tcp_sharing_if_enabled_for_user()
    {
        $this->createTestTcpServer();

        $this->app['config']['expose.admin.validate_auth_tokens'] = true;

        $user = $this->createUser([
            'name' => 'Marcel',
            'can_share_tcp_ports' => 1,
        ]);

        /**
         * We create an expose client that connects to our server and shares
         * the created test HTTP server.
         */
        $client = $this->createClient();
        $response = $this->await($client->connectToServerAndShareTcp(8085, $user->auth_token));

        /**
         * Once the client is connected, we connect to the
         * created tunnel.
         */
        $connector = new \React\Socket\Connector($this->loop);
        $connection = $this->await($connector->connect('127.0.0.1:'.$response->shared_port));

        $this->assertInstanceOf(Connection::class, $connection);
    }

    /** @test */
    public function it_rejects_clients_with_invalid_auth_tokens()
    {
        $this->app['config']['expose.admin.validate_auth_tokens'] = true;

        $this->createTestHttpServer();

        $this->expectException(\UnexpectedValueException::class);

        /**
         * We create an expose client that connects to our server and shares
         * the created test HTTP server.
         */
        $client = $this->createClient();
        $result = $this->await($client->connectToServer('127.0.0.1:8085', 'tunnel'));
    }

    /** @test */
    public function it_allows_clients_with_valid_auth_tokens()
    {
        $this->app['config']['expose.admin.validate_auth_tokens'] = true;

        $user = $this->createUser([
            'name' => 'Marcel',
            'can_specify_subdomains' => 1,
        ]);

        $this->createTestHttpServer();

        /**
         * We create an expose client that connects to our server and shares
         * the created test HTTP server.
         */
        $client = $this->createClient();
        $response = $this->await($client->connectToServer('127.0.0.1:8085', 'tunnel', null, $user->auth_token));

        $this->assertSame('tunnel', $response->subdomain);
    }

    /** @test */
    public function it_rejects_clients_to_specify_custom_subdomains()
    {
        $this->app['config']['expose.admin.validate_auth_tokens'] = true;

        $user = $this->createUser([
            'name' => 'Marcel',
            'can_specify_subdomains' => 0,
        ]);

        $this->createTestHttpServer();

        /**
         * We create an expose client that connects to our server and shares
         * the created test HTTP server.
         */
        $client = $this->createClient();
        $response = $this->await($client->connectToServer('127.0.0.1:8085', 'tunnel', null, $user->auth_token));

        $this->assertNotSame('tunnel', $response->subdomain);
    }

    /** @test */
    public function it_rejects_users_that_want_to_use_a_reserved_subdomain()
    {
        $this->app['config']['expose.admin.validate_auth_tokens'] = true;

        $user = $this->createUser([
            'name' => 'Marcel',
            'can_specify_subdomains' => 1,
        ]);

        $response = $this->await($this->browser->post('http://127.0.0.1:8080/api/subdomains', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'subdomain' => 'reserved',
            'auth_token' => $user->auth_token,
        ])));

        $user = $this->createUser([
            'name' => 'Test-User',
            'can_specify_subdomains' => 1,
        ]);

        $this->createTestHttpServer();

        $this->expectException(\UnexpectedValueException::class);
        /**
         * We create an expose client that connects to our server and shares
         * the created test HTTP server.
         */
        $client = $this->createClient();
        $response = $this->await($client->connectToServer('127.0.0.1:8085', 'reserved', null, $user->auth_token));

        $this->assertSame('reserved', $response->subdomain);
    }

    /** @test */
    public function it_allows_users_that_both_have_the_same_reserved_subdomain()
    {
        $this->app['config']['expose.admin.validate_auth_tokens'] = true;

        $user = $this->createUser([
            'name' => 'Marcel',
            'can_specify_subdomains' => 1,
        ]);

        $response = $this->await($this->browser->post('http://127.0.0.1:8080/api/subdomains', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'subdomain' => 'reserved',
            'auth_token' => $user->auth_token,
        ])));

        $user = $this->createUser([
            'name' => 'Test-User',
            'can_specify_subdomains' => 1,
        ]);

        $response = $this->await($this->browser->post('http://127.0.0.1:8080/api/subdomains', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'subdomain' => 'reserved',
            'auth_token' => $user->auth_token,
        ])));

        $this->createTestHttpServer();
        /**
         * We create an expose client that connects to our server and shares
         * the created test HTTP server.
         */
        $client = $this->createClient();
        $response = $this->await($client->connectToServer('127.0.0.1:8085', 'reserved', null, $user->auth_token));

        $this->assertSame('reserved', $response->subdomain);
    }

    /** @test */
    public function it_rejects_users_that_want_to_use_a_reserved_subdomain_on_a_custom_domain()
    {
        $this->app['config']['expose.admin.validate_auth_tokens'] = true;

        $user = $this->createUser([
            'name' => 'Marcel',
            'can_specify_domains' => 1,
            'can_specify_subdomains' => 1,
        ]);

        $this->await($this->browser->post('http://127.0.0.1:8080/api/domains', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'domain' => 'share.beyondco.de',
            'auth_token' => $user->auth_token,
        ])));

        $this->await($this->browser->post('http://127.0.0.1:8080/api/subdomains', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'subdomain' => 'reserved',
            'domain' => 'share.beyondco.de',
            'auth_token' => $user->auth_token,
        ])));

        $user = $this->createUser([
            'name' => 'Test-User',
            'can_specify_subdomains' => 1,
        ]);

        $this->createTestHttpServer();

        /**
         * We create an expose client that connects to our server and shares
         * the created test HTTP server.
         */
        $client = $this->createClient();
        $response = $this->await($client->connectToServer('127.0.0.1:8085', 'reserved', null, $user->auth_token));

        $this->assertSame('reserved', $response->subdomain);
    }

    /** @test */
    public function it_rejects_users_that_want_to_use_a_subdomain_that_is_already_in_use()
    {
        $this->app['config']['expose.admin.validate_auth_tokens'] = true;

        $user = $this->createUser([
            'name' => 'Marcel',
            'can_specify_subdomains' => 1,
        ]);

        $this->createTestHttpServer();

        $this->expectException(\UnexpectedValueException::class);
        $client = $this->createClient();

        $response = $this->await($client->connectToServer('127.0.0.1:8085', 'taken', null, $user->auth_token));
        $this->assertSame('taken', $response->subdomain);

        $response = $this->await($client->connectToServer('127.0.0.1:8085', 'taken', null, $user->auth_token));
        $this->assertSame('taken', $response->subdomain);
    }

    /** @test */
    public function it_allows_users_to_use_a_subdomain_that_is_already_in_use_on_a_different_shared_host()
    {
        $this->app['config']['expose.admin.validate_auth_tokens'] = true;

        $user = $this->createUser([
            'name' => 'Marcel',
            'can_specify_domains' => 1,
            'can_specify_subdomains' => 1,
        ]);

        $this->await($this->browser->post('http://127.0.0.1:8080/api/domains', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'domain' => 'share.beyondco.de',
            'auth_token' => $user->auth_token,
        ])));

        $this->createTestHttpServer();

        $client = $this->createClient();

        $response = $this->await($client->connectToServer('127.0.0.1:8085', 'taken', null, $user->auth_token));
        $this->assertSame('localhost', $response->server_host);
        $this->assertSame('taken', $response->subdomain);

        $response = $this->await($client->connectToServer('127.0.0.1:8085', 'taken', 'share.beyondco.de', $user->auth_token));
        $this->assertSame('share.beyondco.de', $response->server_host);
        $this->assertSame('taken', $response->subdomain);
    }

    /** @test */
    public function it_allows_users_that_want_to_use_a_reserved_subdomain_on_a_custom_domain()
    {
        $this->app['config']['expose.admin.validate_auth_tokens'] = true;

        $user = $this->createUser([
            'name' => 'Marcel',
            'can_specify_subdomains' => 1,
        ]);

        $this->await($this->browser->post('http://127.0.0.1:8080/api/subdomains', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'subdomain' => 'reserved',
            'auth_token' => $user->auth_token,
        ])));

        $user = $this->createUser([
            'name' => 'Test-User',
            'can_specify_subdomains' => 1,
            'can_specify_domains' => 1,
        ]);

        $this->await($this->browser->post('http://127.0.0.1:8080/api/domains', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'domain' => 'beyondco.de',
            'auth_token' => $user->auth_token,
        ])));

        $this->createTestHttpServer();

        $client = $this->createClient();
        $response = $this->await($client->connectToServer('127.0.0.1:8085', 'reserved', 'beyondco.de', $user->auth_token));

        $this->assertSame('reserved', $response->subdomain);
    }

    /** @test */
    public function it_rejects_users_that_want_to_use_a_reserved_subdomain_on_a_custom_domain_that_does_not_belong_them()
    {
        $this->app['config']['expose.admin.validate_auth_tokens'] = true;

        $user = $this->createUser([
            'name' => 'Marcel',
            'can_specify_subdomains' => 1,
            'can_specify_domains' => 1,
        ]);

        $this->await($this->browser->post('http://127.0.0.1:8080/api/domains', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'domain' => 'beyondco.de',
            'auth_token' => $user->auth_token,
        ])));

        $this->await($this->browser->post('http://127.0.0.1:8080/api/subdomains', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'subdomain' => 'reserved',
            'domain' => 'beyondco.de',
            'auth_token' => $user->auth_token,
        ])));

        $user = $this->createUser([
            'name' => 'Test-User',
            'can_specify_subdomains' => 1,
        ]);

        $this->createTestHttpServer();

        $this->expectException(\UnexpectedValueException::class);

        $client = $this->createClient();
        $response = $this->await($client->connectToServer('127.0.0.1:8085', 'reserved', 'beyondco.de', $user->auth_token));

        $this->assertSame('reserved', $response->subdomain);
    }

    /** @test */
    public function it_allows_users_to_use_their_own_reserved_subdomains()
    {
        $this->app['config']['expose.admin.validate_auth_tokens'] = true;

        $user = $this->createUser([
            'name' => 'Marcel',
            'can_specify_subdomains' => 1,
        ]);

        $response = $this->await($this->browser->post('http://127.0.0.1:8080/api/subdomains', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'subdomain' => 'reserved',
            'auth_token' => $user->auth_token,
        ])));

        $this->createTestHttpServer();
        /**
         * We create an expose client that connects to our server and shares
         * the created test HTTP server.
         */
        $client = $this->createClient();
        $response = $this->await($client->connectToServer('127.0.0.1:8085', 'reserved', null, $user->auth_token));

        $this->assertSame('reserved', $response->subdomain);
    }

    /** @test */
    public function it_allows_users_to_use_their_own_reserved_subdomains_on_custom_domains()
    {
        $this->app['config']['expose.admin.validate_auth_tokens'] = true;

        $user = $this->createUser([
            'name' => 'Marcel',
            'can_specify_domains' => 1,
            'can_specify_subdomains' => 1,
        ]);

        $response = $this->await($this->browser->post('http://127.0.0.1:8080/api/domains', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'domain' => 'share.beyondco.de',
            'auth_token' => $user->auth_token,
        ])));

        $response = $this->await($this->browser->post('http://127.0.0.1:8080/api/subdomains', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'subdomain' => 'reserved',
            'domain' => 'share.beyondco.de',
            'auth_token' => $user->auth_token,
        ])));

        $this->createTestHttpServer();
        /**
         * We create an expose client that connects to our server and shares
         * the created test HTTP server.
         */
        $client = $this->createClient();
        $response = $this->await($client->connectToServer('127.0.0.1:8085', 'reserved', 'share.beyondco.de', $user->auth_token));

        $this->assertSame('reserved', $response->subdomain);
    }

    /** @test */
    public function it_rejects_clients_with_too_many_connections()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->app['config']['expose.admin.validate_auth_tokens'] = false;
        $this->app['config']['expose.admin.maximum_open_connections_per_user'] = 1;

        $this->createTestHttpServer();

        /**
         * We create an expose client that connects to our server and shares
         * the created test HTTP server.
         */
        $client = $this->createClient();
        $this->await($client->connectToServer('127.0.0.1:8085', 'tunnel-1'));
        $this->await($client->connectToServer('127.0.0.1:8085', 'tunnel-2'));
    }

    /** @test */
    public function it_rejects_users_with_custom_max_connection_limit()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->app['config']['expose.admin.validate_auth_tokens'] = true;
        $this->app['config']['expose.admin.maximum_open_connections_per_user'] = 5;

        $user = $this->createUser([
            'name' => 'Marcel',
            'can_specify_subdomains' => 1,
            'max_connections' => 2,
        ]);

        $this->createTestHttpServer();

        /**
         * We create an expose client that connects to our server and shares
         * the created test HTTP server.
         */
        $client = $this->createClient();
        $this->await($client->connectToServer('127.0.0.1:8085', 'tunnel-1', $user->auth_token));
        $this->await($client->connectToServer('127.0.0.1:8085', 'tunnel-2', $user->auth_token));
        $this->await($client->connectToServer('127.0.0.1:8085', 'tunnel-3', $user->auth_token));
    }

    /** @test */
    public function it_allows_clients_to_use_random_subdomains_if_custom_subdomains_are_forbidden()
    {
        $this->app['config']['expose.admin.validate_auth_tokens'] = true;

        $user = $this->createUser([
            'name' => 'Marcel',
            'can_specify_subdomains' => 0,
        ]);

        $this->createTestHttpServer();

        $client = $this->createClient();
        $response = $this->await($client->connectToServer('127.0.0.1:8085', 'foo', null, $user->auth_token));

        $this->assertNotSame('foo', $response->subdomain);
    }

    protected function startServer()
    {
        $this->app['config']['expose.admin.subdomain'] = 'expose';
        $this->app['config']['expose.admin.database'] = ':memory:';

        $this->app['config']['expose.admin.users'] = [
            'username' => 'secret',
        ];

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

        $client = app(Client::class);
        $client->shouldExit(false);

        return $client;
    }

    protected function createUser(array $data)
    {
        $response = $this->await($this->browser->post('http://127.0.0.1:8080/api/users', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode($data)));

        return json_decode($response->getBody()->getContents())->user;
    }

    protected function createTestHttpServer()
    {
        $server = new Server($this->loop, function (ServerRequestInterface $request) {
            return new Response(200, ['Content-Type' => 'text/plain'], 'Hello World!');
        });

        $this->testHttpServer = new \React\Socket\Server(8085, $this->loop);
        $server->listen($this->testHttpServer);
    }

    protected function createTestTcpServer()
    {
        $this->testTcpServer = new \React\Socket\Server(8085, $this->loop);

        $this->testTcpServer->on('connection', function (\React\Socket\ConnectionInterface $connection) {
            $connection->write('Hello '.$connection->getRemoteAddress()."!\n");

            $connection->pipe($connection);
        });
    }
}

<?php

namespace Tests\Feature\Server;

use App\Contracts\ConnectionManager;
use App\Server\Factory;
use Clue\React\Buzz\Browser;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;
use Nyholm\Psr7\Request;
use Ratchet\Server\IoConnection;
use Tests\Feature\TestCase;

class ApiTest extends TestCase
{
    /** @var Browser */
    protected $browser;

    /** @var Factory */
    protected $serverFactory;

    public function setUp(): void
    {
        parent::setUp();

        $this->browser = new Browser($this->loop);
        $this->browser = $this->browser->withOptions([
            'followRedirects' => false,
        ]);

        $this->startServer();
    }

    public function tearDown(): void
    {
        $this->serverFactory->getSocket()->close();

        parent::tearDown();
    }

    /** @test */
    public function it_can_list_all_registered_users()
    {
        /** @var Response $response */
        $this->await($this->browser->post('http://127.0.0.1:8080/api/users', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'name' => 'Marcel',
        ])));

        /** @var Response $response */
        $response = $this->await($this->browser->get('http://127.0.0.1:8080/api/users', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ]));

        $body = json_decode($response->getBody()->getContents());
        $users = $body->paginated->users;

        $this->assertCount(1, $users);
        $this->assertSame('Marcel', $users[0]->name);
        $this->assertSame([], $users[0]->sites);
    }

    /** @test */
    public function it_can_get_user_details()
    {
        /** @var Response $response */
        $this->await($this->browser->post('http://127.0.0.1:8080/api/users', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'name' => 'Marcel',
        ])));

        /** @var Response $response */
        $response = $this->await($this->browser->get('http://127.0.0.1:8080/api/users/1', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ]));

        $body = json_decode($response->getBody()->getContents());
        $user = $body->user;

        $this->assertSame('Marcel', $user->name);
        $this->assertSame([], $user->sites);
    }

    /** @test */
    public function it_can_list_all_currently_connected_sites_from_all_users()
    {
        /** @var Response $response */
        $response = $this->await($this->browser->post('http://127.0.0.1:8080/api/users', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'name' => 'Marcel',
        ])));

        $createdUser = json_decode($response->getBody()->getContents())->user;

        /** @var ConnectionManager $connectionManager */
        $connectionManager = app(ConnectionManager::class);

        $connection = \Mockery::mock(IoConnection::class);
        $connection->httpRequest = new Request('GET', '/?authToken='.$createdUser->auth_token);
        $connectionManager->storeConnection('some-host.test', 'fixed-subdomain', $connection);

        $connection = \Mockery::mock(IoConnection::class);
        $connection->httpRequest = new Request('GET', '/?authToken=some-other-token');
        $connectionManager->storeConnection('some-different-host.test', 'different-subdomain', $connection);

        /** @var Response $response */
        $response = $this->await($this->browser->get('http://127.0.0.1:8080/api/users', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ]));

        $body = json_decode($response->getBody()->getContents());
        $users = $body->paginated->users;

        $this->assertCount(1, $users[0]->sites);
        $this->assertSame('some-host.test', $users[0]->sites[0]->host);
        $this->assertSame('fixed-subdomain', $users[0]->sites[0]->subdomain);
    }

    /** @test */
    public function it_can_list_all_currently_connected_sites()
    {
        /** @var ConnectionManager $connectionManager */
        $connectionManager = app(ConnectionManager::class);

        $connection = \Mockery::mock(IoConnection::class);
        $connection->httpRequest = new Request('GET', '/?authToken=some-token');

        $connectionManager->storeConnection('some-host.test', 'fixed-subdomain', $connection);

        /** @var Response $response */
        $response = $this->await($this->browser->get('http://127.0.0.1:8080/api/sites', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ]));

        $body = json_decode($response->getBody()->getContents());
        $sites = $body->sites;

        $this->assertCount(1, $sites);
        $this->assertSame('some-host.test', $sites[0]->host);
        $this->assertSame('some-token', $sites[0]->auth_token);
        $this->assertSame('fixed-subdomain', $sites[0]->subdomain);
    }

    /** @test */
    public function it_can_list_all_currently_connected_sites_without_auth_tokens()
    {
        /** @var ConnectionManager $connectionManager */
        $connectionManager = app(ConnectionManager::class);

        $connection = \Mockery::mock(IoConnection::class);
        $connection->httpRequest = new Request('GET', '/');

        $connectionManager->storeConnection('some-host.test', 'fixed-subdomain', $connection);

        /** @var Response $response */
        $response = $this->await($this->browser->get('http://127.0.0.1:8080/api/sites', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ]));

        $body = json_decode($response->getBody()->getContents());
        $sites = $body->sites;

        $this->assertCount(1, $sites);
        $this->assertSame('some-host.test', $sites[0]->host);
        $this->assertSame('', $sites[0]->auth_token);
        $this->assertSame('fixed-subdomain', $sites[0]->subdomain);
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
            ->createServer();
    }
}

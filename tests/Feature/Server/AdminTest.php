<?php

namespace Tests\Feature\Server;

use App\Contracts\ConnectionManager;
use App\Server\Factory;
use Clue\React\Buzz\Browser;
use Clue\React\Buzz\Message\ResponseException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;
use Nyholm\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Ratchet\Server\IoConnection;
use Tests\Feature\TestCase;

class AdminTest extends TestCase
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
    public function it_is_protected_using_basic_authentication()
    {
        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage(401);

        /** @var ResponseInterface $response */
        $this->await($this->browser->get('http://127.0.0.1:8080', [
            'Host' => 'expose.localhost',
        ]));
    }

    /** @test */
    public function it_accepts_valid_credentials()
    {
        /** @var ResponseInterface $response */
        $response = $this->await($this->browser->get('http://127.0.0.1:8080/', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
        ]));
        $this->assertSame(301, $response->getStatusCode());
    }

    /** @test */
    public function it_allows_saving_settings()
    {
        $this->app['config']['expose.admin.validate_auth_tokens'] = false;

        /** @var ResponseInterface $response */
        $this->await($this->browser->post('http://127.0.0.1:8080/api/settings', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'validate_auth_tokens' => true,
        ])));

        $this->assertTrue(config('expose.admin.validate_auth_tokens'));
    }

    /** @test */
    public function it_can_create_users()
    {
        /** @var Response $response */
        $response = $this->await($this->browser->post('http://127.0.0.1:8080/api/users', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'name' => 'Marcel',
        ])));

        $responseData = json_decode($response->getBody()->getContents());
        $this->assertSame('Marcel', $responseData->user->name);

        $this->assertDatabaseHasResults('SELECT * FROM users WHERE name = "Marcel"');
    }

    /** @test */
    public function it_can_delete_users()
    {
        /** @var Response $response */
        $this->await($this->browser->post('http://127.0.0.1:8080/api/users', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ], json_encode([
            'name' => 'Marcel',
        ])));

        $this->await($this->browser->delete('http://127.0.0.1:8080/api/users/1', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ]));

        $this->assertDatabaseHasNoResults('SELECT * FROM users WHERE name = "Marcel"');
    }

    /** @test */
    public function it_can_list_all_users()
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
        $response = $this->await($this->browser->get('http://127.0.0.1:8080/users', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ]));

        $body = $response->getBody()->getContents();

        $this->assertTrue(Str::contains($body, 'Marcel'));
    }

    /** @test */
    public function it_can_list_all_currently_connected_sites()
    {
        /** @var ConnectionManager $connectionManager */
        $connectionManager = app(ConnectionManager::class);

        $connection = \Mockery::mock(IoConnection::class);
        $connection->httpRequest = new Request('GET', '/?authToken=some-token');

        $connectionManager->storeConnection('some-host.text', 'fixed-subdomain', $connection);

        /** @var Response $response */
        $response = $this->await($this->browser->get('http://127.0.0.1:8080/sites', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode('username:secret'),
            'Content-Type' => 'application/json',
        ]));

        $body = $response->getBody()->getContents();

        $this->assertTrue(Str::contains($body, 'some-host.text'));
        $this->assertTrue(Str::contains($body, 'fixed-subdomain'));
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

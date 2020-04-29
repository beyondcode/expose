<?php

namespace Tests\Feature\Server;

use App\Http\Server;
use App\Server\Factory;
use Clue\React\Buzz\Browser;
use Clue\React\Buzz\Message\ResponseException;
use Psr\Http\Message\ResponseInterface;
use React\Socket\Connector;
use Tests\Feature\TestCase;

class AdminTest extends TestCase
{
    /** @var Browser */
    protected $browser;

    /** @var Factory  */
    protected $serverFactory;

    public function setUp(): void
    {
        parent::setUp();

        $this->browser = new Browser($this->loop);

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
            'Host' => 'expose.localhost'
        ]));
    }

    /** @test */
    public function it_accepts_valid_credentials()
    {
        $this->app['config']['expose.admin.users'] = [
            'username' => 'secret',
        ];

        /** @var ResponseInterface $response */
        $response = $this->await($this->browser->get('http://127.0.0.1:8080', [
            'Host' => 'expose.localhost',
            'Authorization' => base64_encode("username:secret"),
        ]));
        $this->assertSame(200, $response->getStatusCode());
    }

    protected function startServer()
    {
        $this->serverFactory = new Factory();

        $this->serverFactory->setLoop($this->loop)
            ->createServer();
    }
}

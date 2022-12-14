<?php

namespace Tests\Feature\Client;

use App\Client\Configuration;
use App\Client\Factory;
use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use React\Http\Message\ResponseException;
use Tests\Feature\TestCase;

class FileserverTest extends TestCase
{
    /** @var Browser */
    protected $browser;

    /** @var Factory */
    protected $clientFactory;

    /** @var string */
    protected $fileserverUrl;

    public function setUp(): void
    {
        parent::setUp();

        $this->browser = new Browser($this->loop);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->clientFactory->getFileserver()->getSocket()->close();
    }

    /** @test */
    public function accessing_the_fileserver_works()
    {
        $this->shareFolder(__DIR__);

        /** @var ResponseInterface $response */
        $response = $this->await($this->browser->get('http://'.$this->fileserverUrl));

        $this->assertSame(200, $response->getStatusCode());
    }

    /** @test */
    public function accessing_invalid_files_returns_404()
    {
        $this->shareFolder(__DIR__);

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage(404);

        /** @var ResponseInterface $response */
        $response = $this->await($this->browser->get('http://'.$this->fileserverUrl.'/invalid-file'));

        $this->assertSame(404, $response->getStatusCode());
    }

    /** @test */
    public function it_can_return_filtered_responses()
    {
        $this->shareFolder(__DIR__.'/../../fixtures', '*.md');

        $this->expectException(ResponseException::class);
        $this->expectExceptionMessage(404);

        /** @var ResponseInterface $response */
        $response = $this->await($this->browser->get('http://'.$this->fileserverUrl.'/test.txt'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('test-file'.PHP_EOL, $response->getBody()->getContents());
    }

    /** @test */
    public function it_can_return_file_responses()
    {
        $this->shareFolder(__DIR__.'/../../fixtures');

        /** @var ResponseInterface $response */
        $response = $this->await($this->browser->get('http://'.$this->fileserverUrl.'/test.txt'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('test-file'.PHP_EOL, $response->getBody()->getContents());
    }

    /** @test */
    public function it_can_return_file_responses_for_valid_filtered_files()
    {
        $this->shareFolder(__DIR__.'/../../fixtures', '*.txt');

        /** @var ResponseInterface $response */
        $response = $this->await($this->browser->get('http://'.$this->fileserverUrl.'/test.txt'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('test-file'.PHP_EOL, $response->getBody()->getContents());
    }

    protected function shareFolder(string $folder, string $name = '')
    {
        app()->singleton(Configuration::class, function ($app) {
            return new Configuration('localhost', '8080', false);
        });

        $factory = (new Factory())->setLoop($this->loop);
        $this->fileserverUrl = $factory->createFileServer($folder, $name);
        $this->clientFactory = $factory;
    }
}

<?php

namespace App\Server;

use App\Contracts\ConnectionManager as ConnectionManagerContract;
use App\Contracts\SubdomainGenerator;
use App\HttpServer\HttpServer;
use App\Server\Connections\ConnectionManager;
use App\Server\Http\Controllers\ControlMessageController;
use App\Server\Http\Controllers\TunnelMessageController;
use App\Server\SubdomainGenerator\RandomSubdomainGenerator;
use Ratchet\Http\Router;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\Socket\Server;
use React\EventLoop\LoopInterface;
use React\EventLoop\Factory as LoopFactory;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Factory
{
    /** @var string */
    protected $host = '127.0.0.1';

    /** @var string */
    protected $hostname = 'localhost';

    /** @var int */
    protected $port = 8080;

    /** @var \React\EventLoop\LoopInterface */
    protected $loop;

    public function __construct()
    {
        $this->loop = LoopFactory::create();
    }

    public function setHost(string $host)
    {
        $this->host = $host;

        return $this;
    }

    public function setPort(int $port)
    {
        $this->port = $port;

        return $this;
    }

    public function setLoop(LoopInterface $loop)
    {
        $this->loop = $loop;

        return $this;
    }

    public function setHostname(string $hostname)
    {
        $this->hostname = $hostname;

        return $this;
    }

    protected function getRoutes(): RouteCollection
    {
        $routes = new RouteCollection();

        $routes->add('control',
            new Route('/__expose_control__', [
                '_controller' => new WsServer(app(ControlMessageController::class))
            ], [], [], null, [], []
            )
        );

        $routes->add('tunnel',
            new Route('/{__catchall__}', [
                '_controller' => app(TunnelMessageController::class),
            ], [
                '__catchall__' => '.*'
            ]));

        return $routes;
    }

    protected function bindConfiguration()
    {
        app()->singleton(Configuration::class, function ($app) {
            return new Configuration($this->hostname, $this->port);
        });
    }

    protected function bindSubdomainGenerator()
    {
        app()->singleton(SubdomainGenerator::class, function ($app) {
            return $app->make(RandomSubdomainGenerator::class);
        });
    }

    protected function bindConnectionManager()
    {
        app()->singleton(ConnectionManagerContract::class, function ($app) {
            return $app->make(ConnectionManager::class);
        });
    }

    public function createServer()
    {
        $socket = new Server("{$this->host}:{$this->port}", $this->loop);

        $this->bindConfiguration();

        $this->bindSubdomainGenerator();

        $this->bindConnectionManager();

        $urlMatcher = new UrlMatcher($this->getRoutes(), new RequestContext);

        $router = new Router($urlMatcher);

        $http = new HttpServer($router);

        return new IoServer($http, $socket, $this->loop);
    }

}

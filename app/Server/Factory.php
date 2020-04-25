<?php

namespace App\Server;

use App\Contracts\ConnectionManager as ConnectionManagerContract;
use App\Contracts\SubdomainGenerator;
use App\HttpServer\HttpServer;
use App\Server\Connections\ConnectionManager;
use App\Server\Http\Controllers\Admin\DeleteUsersController;
use App\Server\Http\Controllers\Admin\ListSitesController;
use App\Server\Http\Controllers\Admin\ListUsersController;
use App\Server\Http\Controllers\Admin\StoreUsersController;
use App\Server\Http\Controllers\ControlMessageController;
use App\Server\Http\Controllers\TunnelMessageController;
use App\Server\SubdomainGenerator\RandomSubdomainGenerator;
use Clue\React\SQLite\DatabaseInterface;
use Ratchet\Http\Router;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\Socket\Server;
use React\EventLoop\LoopInterface;
use React\EventLoop\Factory as LoopFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
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

    /** @var RouteCollection */
    protected $routes;

    public function __construct()
    {
        $this->loop = LoopFactory::create();
        $this->routes = new RouteCollection();
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

    protected function addExposeRoutes()
    {
        $wsServer = new WsServer(app(ControlMessageController::class));
        $wsServer->enableKeepAlive($this->loop);

        $this->routes->add('control',
            new Route('/__expose_control__', [
                '_controller' => $wsServer
            ], [], [], null, [], []
            )
        );

        $this->routes->add('tunnel',
            new Route('/{__catchall__}', [
                '_controller' => app(TunnelMessageController::class),
            ], [
                '__catchall__' => '.*'
            ]));
    }

    protected function addAdminRoutes()
    {
        $this->routes->add('admin.users.index',
            new Route('/expose/users', [
                '_controller' => app(ListUsersController::class),
            ], [], [], null, [], ['GET'])
        );

        $this->routes->add('admin.users.store',
            new Route('/expose/users', [
                '_controller' => app(StoreUsersController::class),
            ], [], [], null, [], ['POST'])
        );

        $this->routes->add('admin.users.delete',
            new Route('/expose/users/delete/{id}', [
                '_controller' => app(DeleteUsersController::class),
            ], [], [], null, [], ['DELETE'])
        );

        $this->routes->add('admin.sites.index',
            new Route('/expose/sites', [
                '_controller' => app(ListSitesController::class),
            ], [], [], null, [], ['GET'])
        );
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

        $this->bindDatabase();

        $this->ensureDatabaseIsInitialized();

        $this->bindConnectionManager();

        $this->addAdminRoutes();

        $this->addExposeRoutes();

        $urlMatcher = new UrlMatcher($this->routes, new RequestContext);

        $router = new Router($urlMatcher);

        $http = new HttpServer($router);

        return new IoServer($http, $socket, $this->loop);
    }

    protected function bindDatabase()
    {
        app()->singleton(DatabaseInterface::class, function() {
            $factory = new \Clue\React\SQLite\Factory($this->loop);
            return $factory->openLazy(base_path('database/expose.db'));
        });
    }

    protected function ensureDatabaseIsInitialized()
    {
        /** @var DatabaseInterface $db */
        $db = app(DatabaseInterface::class);

        $migrations = (new Finder())
            ->files()
            ->ignoreDotFiles(true)
            ->in(database_path('migrations'))
            ->name('*.sql');

        /** @var SplFileInfo $migration */
        foreach ($migrations as $migration) {
            $db->exec($migration->getContents());
        }
    }

    public function validateAuthTokens(bool $validate)
    {
        config()->set('expose.validate_auth_tokens', $validate);

        return $this;
    }

}

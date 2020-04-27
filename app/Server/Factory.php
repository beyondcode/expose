<?php

namespace App\Server;

use App\Contracts\ConnectionManager as ConnectionManagerContract;
use App\Contracts\SubdomainGenerator;
use App\HttpServer\HttpServer;
use App\Server\Connections\ConnectionManager;
use App\Server\Http\Controllers\Admin\DeleteUsersController;
use App\Server\Http\Controllers\Admin\ListSitesController;
use App\Server\Http\Controllers\Admin\ListUsersController;
use App\Server\Http\Controllers\Admin\LoginController;
use App\Server\Http\Controllers\Admin\StoreUsersController;
use App\Server\Http\Controllers\Admin\VerifyLoginController;
use App\Server\Http\Controllers\ControlMessageController;
use App\Server\Http\Controllers\TunnelMessageController;
use App\Server\Http\RouteGenerator;
use App\Server\Http\Router;
use App\Server\SubdomainGenerator\RandomSubdomainGenerator;
use Clue\React\SQLite\DatabaseInterface;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\Socket\Server;
use React\EventLoop\LoopInterface;
use React\EventLoop\Factory as LoopFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
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

    /** @var RouteGenerator */
    protected $router;

    public function __construct()
    {
        $this->loop = LoopFactory::create();
        $this->router = new RouteGenerator();
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

    protected function addTunnelRoute()
    {
        $this->router->addSymfonyRoute('tunnel',
            new Route('/{__catchall__}', [
                '_controller' => app(TunnelMessageController::class),
            ], [
                '__catchall__' => '.*'
            ]));
    }

    protected function addControlConnectionRoute(): WsServer
    {
        $wsServer = new WsServer(app(ControlMessageController::class));

        $this->router->addSymfonyRoute('expose-control',
            new Route('/expose/control', [
                '_controller' => $wsServer,
            ], [], [], '', [], [], 'request.headers.get("x-expose-control") matches "/enabled/i"'));

        return $wsServer;
    }

    protected function addAdminRoutes()
    {
        $adminCondition = 'request.headers.get("Host") matches "/'.config('expose.dashboard_subdomain').'./i"';

        $this->router->get('/', LoginController::class, $adminCondition);
        $this->router->post('/', VerifyLoginController::class, $adminCondition);
        $this->router->get('/users', ListUsersController::class, $adminCondition);
        $this->router->post('/users', StoreUsersController::class, $adminCondition);
        $this->router->delete('/users/delete/{id}', DeleteUsersController::class, $adminCondition);
        $this->router->get('/sites', ListSitesController::class, $adminCondition);
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

        $this->addTunnelRoute();

        $controlConnection = $this->addControlConnectionRoute();

        $urlMatcher = new UrlMatcher($this->router->getRoutes(), new RequestContext);

        $router = new Router($urlMatcher);

        $http = new HttpServer($router);

        $server = new IoServer($http, $socket, $this->loop);

        $controlConnection->enableKeepAlive($this->loop);

        return $server;
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

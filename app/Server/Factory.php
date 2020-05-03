<?php

namespace App\Server;

use App\Contracts\ConnectionManager as ConnectionManagerContract;
use App\Contracts\SubdomainGenerator;
use App\Contracts\UserRepository;
use App\Http\Server as HttpServer;
use App\Server\Connections\ConnectionManager;
use App\Server\Http\Controllers\Admin\DeleteUsersController;
use App\Server\Http\Controllers\Admin\DisconnectSiteController;
use App\Server\Http\Controllers\Admin\GetSettingsController;
use App\Server\Http\Controllers\Admin\GetSitesController;
use App\Server\Http\Controllers\Admin\GetUsersController;
use App\Server\Http\Controllers\Admin\ListSitesController;
use App\Server\Http\Controllers\Admin\ListUsersController;
use App\Server\Http\Controllers\Admin\RedirectToUsersController;
use App\Server\Http\Controllers\Admin\SaveSettingsController;
use App\Server\Http\Controllers\Admin\ShowSettingsController;
use App\Server\Http\Controllers\Admin\StoreUsersController;
use App\Server\Http\Controllers\ControlMessageController;
use App\Server\Http\Controllers\TunnelMessageController;
use App\Http\RouteGenerator;
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

    /** @var Server */
    protected $socket;

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
        $adminCondition = 'request.headers.get("Host") matches "/^'.config('expose.admin.subdomain').'\./i"';

        $this->router->get('/', RedirectToUsersController::class, $adminCondition);
        $this->router->get('/users', ListUsersController::class, $adminCondition);
        $this->router->get('/settings', ShowSettingsController::class, $adminCondition);
        $this->router->get('/sites', ListSitesController::class, $adminCondition);

        $this->router->get('/api/settings', GetSettingsController::class, $adminCondition);
        $this->router->post('/api/settings', SaveSettingsController::class, $adminCondition);
        $this->router->get('/api/users', GetUsersController::class, $adminCondition);
        $this->router->post('/api/users', StoreUsersController::class, $adminCondition);
        $this->router->delete('/api/users/{id}', DeleteUsersController::class, $adminCondition);
        $this->router->get('/api/sites', GetSitesController::class, $adminCondition);
        $this->router->delete('/api/sites/{id}', DisconnectSiteController::class, $adminCondition);
    }

    protected function bindConfiguration()
    {
        app()->singleton(Configuration::class, function ($app) {
            return new Configuration($this->hostname, $this->port);
        });

        return $this;
    }

    protected function bindSubdomainGenerator()
    {
        app()->singleton(SubdomainGenerator::class, function ($app) {
            return $app->make(config('expose.admin.subdomain_generator'));
        });

        return $this;
    }

    protected function bindConnectionManager()
    {
        app()->singleton(ConnectionManagerContract::class, function ($app) {
            return $app->make(ConnectionManager::class);
        });

        return $this;
    }

    public function createServer()
    {
        $this->socket = new Server("{$this->host}:{$this->port}", $this->loop);

        $this->bindConfiguration()
            ->bindSubdomainGenerator()
            ->bindUserRepository()
            ->bindDatabase()
            ->ensureDatabaseIsInitialized()
            ->bindConnectionManager()
            ->addAdminRoutes();

        $controlConnection = $this->addControlConnectionRoute();

        $this->addTunnelRoute();

        $urlMatcher = new UrlMatcher($this->router->getRoutes(), new RequestContext);

        $router = new Router($urlMatcher);

        $http = new HttpServer($router);

        $server = new IoServer($http, $this->socket, $this->loop);

        $controlConnection->enableKeepAlive($this->loop);

        return $server;
    }

    public function getSocket(): Server
    {
        return $this->socket;
    }

    protected function bindUserRepository()
    {
        app()->singleton(UserRepository::class, function() {
            return app(config('expose.admin.user_repository'));
        });

        return $this;
    }

    protected function bindDatabase()
    {
        app()->singleton(DatabaseInterface::class, function() {
            $factory = new \Clue\React\SQLite\Factory($this->loop);
            return $factory->openLazy(config('expose.admin.database', ':memory:'));
        });

        return $this;
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

        return $this;
    }

    public function validateAuthTokens(bool $validate)
    {
        config()->set('expose.admin.validate_auth_tokens', $validate);

        return $this;
    }

}

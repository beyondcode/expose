<?php

namespace App\Server;

use App\Contracts\ConnectionManager as ConnectionManagerContract;
use App\Contracts\DomainRepository;
use App\Contracts\LoggerRepository;
use App\Contracts\StatisticsCollector;
use App\Contracts\StatisticsRepository;
use App\Contracts\SubdomainGenerator;
use App\Contracts\SubdomainRepository;
use App\Contracts\UserRepository;
use App\Http\RouteGenerator;
use App\Http\Server as HttpServer;
use App\Server\Connections\ConnectionManager;
use App\Server\DomainRepository\DatabaseDomainRepository;
use App\Server\Http\Controllers\Admin\DeleteSubdomainController;
use App\Server\Http\Controllers\Admin\DeleteUsersController;
use App\Server\Http\Controllers\Admin\DisconnectSiteController;
use App\Server\Http\Controllers\Admin\DisconnectTcpConnectionController;
use App\Server\Http\Controllers\Admin\GetLogsController;
use App\Server\Http\Controllers\Admin\GetLogsForSubdomainController;
use App\Server\Http\Controllers\Admin\GetSettingsController;
use App\Server\Http\Controllers\Admin\GetSiteDetailsController;
use App\Server\Http\Controllers\Admin\GetSitesController;
use App\Server\Http\Controllers\Admin\GetStatisticsController;
use App\Server\Http\Controllers\Admin\GetTcpConnectionsController;
use App\Server\Http\Controllers\Admin\GetUserDetailsController;
use App\Server\Http\Controllers\Admin\GetUsersController;
use App\Server\Http\Controllers\Admin\ListSitesController;
use App\Server\Http\Controllers\Admin\ListTcpConnectionsController;
use App\Server\Http\Controllers\Admin\ListUsersController;
use App\Server\Http\Controllers\Admin\RedirectToUsersController;
use App\Server\Http\Controllers\Admin\ShowSettingsController;
use App\Server\Http\Controllers\Admin\StoreDomainController;
use App\Server\Http\Controllers\Admin\StoreSettingsController;
use App\Server\Http\Controllers\Admin\StoreSubdomainController;
use App\Server\Http\Controllers\Admin\StoreUsersController;
use App\Server\Http\Controllers\ControlMessageController;
use App\Server\Http\Controllers\TunnelMessageController;
use App\Server\Http\Router;
use App\Server\LoggerRepository\NullLogger;
use App\Server\StatisticsCollector\DatabaseStatisticsCollector;
use App\Server\StatisticsRepository\DatabaseStatisticsRepository;
use App\Server\SubdomainRepository\DatabaseSubdomainRepository;
use Clue\React\SQLite\DatabaseInterface;
use Phar;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use React\Socket\Server;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;

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
                '__catchall__' => '.*',
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
        $adminCondition = 'request.headers.get("Host") matches "/^'.config('expose.admin.subdomain').'\\\\./i"';

        $this->router->get('/', RedirectToUsersController::class, $adminCondition);
        $this->router->get('/users', ListUsersController::class, $adminCondition);
        $this->router->get('/settings', ShowSettingsController::class, $adminCondition);
        $this->router->get('/sites', ListSitesController::class, $adminCondition);
        $this->router->get('/tcp', ListTcpConnectionsController::class, $adminCondition);

        $this->router->get('/api/statistics', GetStatisticsController::class, $adminCondition);
        $this->router->get('/api/settings', GetSettingsController::class, $adminCondition);
        $this->router->post('/api/settings', StoreSettingsController::class, $adminCondition);

        $this->router->get('/api/users', GetUsersController::class, $adminCondition);
        $this->router->post('/api/users', StoreUsersController::class, $adminCondition);
        $this->router->get('/api/users/{id}', GetUserDetailsController::class, $adminCondition);
        $this->router->delete('/api/users/{id}', DeleteUsersController::class, $adminCondition);

        $this->router->get('/api/logs', GetLogsController::class, $adminCondition);
        $this->router->get('/api/logs/{subdomain}', GetLogsForSubdomainController::class, $adminCondition);

        $this->router->post('/api/domains', StoreDomainController::class, $adminCondition);
        $this->router->delete('/api/domains/{domain}', DeleteSubdomainController::class, $adminCondition);

        $this->router->post('/api/subdomains', StoreSubdomainController::class, $adminCondition);
        $this->router->delete('/api/subdomains/{subdomain}', DeleteSubdomainController::class, $adminCondition);

        $this->router->get('/api/sites', GetSitesController::class, $adminCondition);
        $this->router->get('/api/sites/{site}', GetSiteDetailsController::class, $adminCondition);
        $this->router->delete('/api/sites/{id}', DisconnectSiteController::class, $adminCondition);

        $this->router->get('/api/tcp', GetTcpConnectionsController::class, $adminCondition);
        $this->router->delete('/api/tcp/{id}', DisconnectTcpConnectionController::class, $adminCondition);
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
            ->bindLoggerRepository()
            ->bindSubdomainRepository()
            ->bindDomainRepository()
            ->bindDatabase()
            ->ensureDatabaseIsInitialized()
            ->registerStatisticsCollector()
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
        app()->singleton(UserRepository::class, function () {
            return app(config('expose.admin.user_repository'));
        });

        return $this;
    }

    protected function bindSubdomainRepository()
    {
        app()->singleton(SubdomainRepository::class, function () {
            return app(config('expose.admin.subdomain_repository', DatabaseSubdomainRepository::class));
        });

        return $this;
    }

    protected function bindLoggerRepository()
    {
        app()->singleton(LoggerRepository::class, function () {
            return app(config('expose.admin.logger_repository', NullLogger::class));
        });

        return $this;
    }

    protected function bindDomainRepository()
    {
        app()->singleton(DomainRepository::class, function () {
            return app(config('expose.admin.domain_repository', DatabaseDomainRepository::class));
        });

        return $this;
    }

    protected function bindDatabase()
    {
        app()->singleton(DatabaseInterface::class, function () {
            $factory = new \Clue\React\SQLite\Factory($this->loop);

            $options = ['worker_command' => Phar::running(false) ? Phar::running(false).' --sqlite-worker' : null];

            return $factory->openLazy(
                config('expose.admin.database', ':memory:'),
                null,
                $options,
            );
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
            ->name('*.sql')
            ->sortByName();

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

    protected function registerStatisticsCollector()
    {
        if (config('expose.admin.statistics.enable_statistics', true) === false) {
            return $this;
        }

        app()->singleton(StatisticsRepository::class, function () {
            return app(config('expose.admin.statistics.repository', DatabaseStatisticsRepository::class));
        });

        app()->singleton(StatisticsCollector::class, function () {
            return app(DatabaseStatisticsCollector::class);
        });

        $intervalInSeconds = config('expose.admin.statistics.interval_in_seconds', 3600);

        $this->loop->addPeriodicTimer($intervalInSeconds, function () {
            app(StatisticsCollector::class)->save();
        });

        return $this;
    }
}

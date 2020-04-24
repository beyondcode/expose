<?php

namespace App\Client;

use App\Client\Http\HttpClient;
use App\HttpServer\App;
use App\HttpServer\Controllers\AttachDataToLogController;
use App\HttpServer\Controllers\ClearLogsController;
use App\HttpServer\Controllers\DashboardController;
use App\HttpServer\Controllers\LogController;
use App\HttpServer\Controllers\ReplayLogController;
use App\HttpServer\Controllers\StoreLogController;
use App\WebSockets\Socket;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\LoopInterface;
use Symfony\Component\Routing\Route;
use React\EventLoop\Factory as LoopFactory;

class Factory
{
    /** @var string */
    protected $host = 'localhost';

    /** @var int */
    protected $port = 8080;

    /** @var string */
    protected $auth = '';

    /** @var string */
    protected $authToken = '';

    /** @var \React\EventLoop\LoopInterface */
    protected $loop;

    /** @var App */
    protected $app;

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

    public function setAuth(?string $auth)
    {
        $this->auth = $auth;

        return $this;
    }

    public function setAuthToken(?string $authToken)
    {
        $this->authToken = $authToken;

        return $this;
    }

    public function setLoop(LoopInterface $loop)
    {
        $this->loop = $loop;

        return $this;
    }

    protected function bindConfiguration()
    {
        app()->singleton(Configuration::class, function ($app) {
            return new Configuration($this->host, $this->port, $this->auth, $this->authToken);
        });
    }

    protected function bindProxyManager()
    {
        app()->bind(ProxyManager::class, function ($app) {
            return new ProxyManager($app->make(Configuration::class), $this->loop);
        });
    }

    public function createClient($sharedUrl, $subdomain = null, $auth = null)
    {
        $this->bindConfiguration();

        $this->bindProxyManager();

        app(Client::class)->share($sharedUrl, $subdomain);

        return $this;
    }

    protected function addRoutes()
    {
        $dashboardRoute = new Route('/', ['_controller' => app(DashboardController::class)], [], [], null, [], ['GET']);
        $logRoute = new Route('/logs', ['_controller' => app(LogController::class)], [], [], null, [], ['GET']);
        $storeLogRoute = new Route('/logs', ['_controller' => app(StoreLogController::class)], [], [], null, [], ['POST']);
        $replayLogRoute = new Route('/replay/{log}', ['_controller' => app(ReplayLogController::class)], [], [], null, [], ['GET']);
        $attachLogDataRoute = new Route('/logs/{request_id}/data', ['_controller' => app(AttachDataToLogController::class)], [], [], null, [], ['POST']);
        $clearLogsRoute = new Route('/logs/clear', ['_controller' => app(ClearLogsController::class)], [], [], null, [], ['GET']);

        $this->app->route('/socket', new WsServer(new Socket()), ['*']);

        $this->app->routes->add('dashboard', $dashboardRoute);
        $this->app->routes->add('logs', $logRoute);
        $this->app->routes->add('storeLogs', $storeLogRoute);
        $this->app->routes->add('replayLog', $replayLogRoute);
        $this->app->routes->add('attachLogData', $attachLogDataRoute);
        $this->app->routes->add('clearLogs', $clearLogsRoute);
    }

    protected function detectNextFreeDashboardPort($port = 4040): int
    {
        while (is_resource(@fsockopen('127.0.0.1', $port))) {
            $port++;
        }

        return $port;
    }

    public function createHttpServer()
    {
        $dashboardPort = $this->detectNextFreeDashboardPort();

        $this->loop->futureTick(function () use ($dashboardPort) {
            $dashboardUrl = "http://127.0.0.1:{$dashboardPort}/";

            echo("Started Dashboard on port {$dashboardPort}" . PHP_EOL);

            echo('If the dashboard does not automatically open, visit: ' . $dashboardUrl . PHP_EOL);
        });

        $this->app = new App('127.0.0.1', $dashboardPort, '0.0.0.0', $this->loop);

        $this->addRoutes();

        return $this;
    }

    public function run()
    {
        $this->loop->run();
    }

}

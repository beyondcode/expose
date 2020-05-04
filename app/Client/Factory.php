<?php

namespace App\Client;

use App\Client\Http\Controllers\CreateTunnelController;
use App\Client\Http\Controllers\PushLogsToDashboardController;
use App\Client\Http\HttpClient;
use App\Http\App;
use App\Client\Http\Controllers\AttachDataToLogController;
use App\Client\Http\Controllers\ClearLogsController;
use App\Client\Http\Controllers\DashboardController;
use App\Client\Http\Controllers\LogController;
use App\Client\Http\Controllers\ReplayLogController;
use App\Http\Controllers\StoreLogController;
use App\Http\RouteGenerator;
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

    /** @var \React\EventLoop\LoopInterface */
    protected $loop;

    /** @var App */
    protected $app;

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

    public function setAuth(?string $auth)
    {
        $this->auth = $auth;

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
            return new Configuration($this->host, $this->port, $this->auth);
        });
    }

    protected function bindClient()
    {
        app()->singleton('expose.client', function ($app) {
            return $app->make(Client::class);
        });
    }

    protected function bindProxyManager()
    {
        app()->bind(ProxyManager::class, function ($app) {
            return new ProxyManager($app->make(Configuration::class), $this->loop);
        });
    }

    public function createClient()
    {
        $this->bindClient();

        $this->bindConfiguration();

        $this->bindProxyManager();

        return $this;
    }

    public function share($sharedUrl, $subdomain = null)
    {
        app('expose.client')->share($sharedUrl, $subdomain);

        return $this;
    }

    protected function addRoutes()
    {
        $this->router->get('/', DashboardController::class);

        $this->router->post('/api/tunnel', CreateTunnelController::class);
        $this->router->get('/api/logs', LogController::class);
        $this->router->post('/api/logs', PushLogsToDashboardController::class);
        $this->router->get('/api/replay/{log}', ReplayLogController::class);
        $this->router->post('/api/logs/{request_id}/data', AttachDataToLogController::class);
        $this->router->get('/api/logs/clear', ClearLogsController::class);

        $this->app->route('/socket', new WsServer(new Socket()), ['*']);

        foreach ($this->router->getRoutes()->all() as $name => $route) {
            $this->app->routes->add($name, $route);
        }
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

            echo('You can visit the dashboard at: ' . $dashboardUrl . PHP_EOL);
        });

        $this->app = new App('127.0.0.1', $dashboardPort, '0.0.0.0', $this->loop);

        $this->addRoutes();

        return $this;
    }

    public function getApp(): App
    {
        return $this->app;
    }

    public function run()
    {
        $this->loop->run();
    }

}

<?php

namespace App\Client;

use App\HttpServer\App;
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

    public function setLoop(LoopInterface $loop)
    {
        $this->loop = $loop;

        return $this;
    }

    public function createClient($sharedUrl, $subdomain = null)
    {
        $client = new Client($this->loop, $this->host, $this->port);
        $client->share($sharedUrl, $subdomain);

        return $this;
    }

    protected function addRoutes()
    {
        $dashboardRoute = new Route('/', ['_controller' => new DashboardController()], [], [], null, [], ['GET']);
        $logRoute = new Route('/logs', ['_controller' => new LogController()], [], [], null, [], ['GET']);
        $storeLogRoute = new Route('/logs', ['_controller' => new StoreLogController()], [], [], null, [], ['POST']);
        $replayLogRoute = new Route('/replay/{log}', ['_controller' => new ReplayLogController()], [], [], null, [], ['GET']);

        $this->app->route('/socket', new WsServer(new Socket()), ['*']);

        $this->app->routes->add('dashboard', $dashboardRoute);
        $this->app->routes->add('logs', $logRoute);
        $this->app->routes->add('storeLogs', $storeLogRoute);
        $this->app->routes->add('replayLog', $replayLogRoute);
    }

    public function createHttpServer()
    {
        $this->loop->futureTick(function () {
            $dashboardUrl = 'http://127.0.0.1:4040/';

            echo('Started Dashboard on port 4040'. PHP_EOL);

            echo('If the dashboard does not automatically open, visit: '.$dashboardUrl . PHP_EOL);
        });

        $this->app = new App('127.0.0.1', 4040, '0.0.0.0', $this->loop);

        $this->addRoutes();

        return $this;
    }

    public function run()
    {
        $this->loop->run();
    }

}

<?php

namespace App\HttpServer;

use Ratchet\Http\Router;
use Ratchet\Server\IoServer;
use React\EventLoop\LoopInterface;
use React\Socket\Server as Reactor;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class App extends \Ratchet\App
{
    public function __construct($httpHost, $port, $address, LoopInterface $loop)
    {
        $this->httpHost = $httpHost;
        $this->port = $port;

        $socket = new Reactor($address.':'.$port, $loop);

        $this->routes = new RouteCollection;

        $urlMatcher = new UrlMatcher($this->routes, new RequestContext);

        $router = new Router($urlMatcher);

        $httpServer = new HttpServer($router);

        $this->_server = new IoServer($httpServer, $socket, $loop);
    }
}

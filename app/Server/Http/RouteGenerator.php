<?php

namespace App\Server\Http;

use Ratchet\WebSocket\MessageComponentInterface;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\LoopInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteGenerator
{
    /** @var \Symfony\Component\Routing\RouteCollection */
    protected $routes;

    public function __construct()
    {
        $this->routes = new RouteCollection;
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    public function get(string $uri, $action, string $condition = '')
    {
        $this->addRoute('GET', $uri, $action, $condition);
    }

    public function post(string $uri, $action, string $condition = '')
    {
        $this->addRoute('POST', $uri, $action, $condition);
    }

    public function put(string $uri, $action, string $condition = '')
    {
        $this->addRoute('PUT', $uri, $action, $condition);
    }

    public function patch(string $uri, $action, string $condition = '')
    {
        $this->addRoute('PATCH', $uri, $action, $condition);
    }

    public function delete(string $uri, $action, string $condition = '')
    {
        $this->addRoute('DELETE', $uri, $action, $condition);
    }

    public function addRoute(string $method, string $uri, $action, string $condition = '')
    {
        $this->routes->add("{$method}-($uri}", $this->getRoute($method, $uri, $action, $condition));
    }

    public function addSymfonyRoute(string $name, Route $route)
    {
        $this->routes->add($name, $route);
    }

    protected function getRoute(string $method, string $uri, $action, string $condition = ''): Route
    {
        $action = is_subclass_of($action, MessageComponentInterface::class)
            ? $this->createWebSocketsServer($action)
            : app($action);

        return new Route($uri, ['_controller' => $action], [], [], null, [], [$method], $condition);
    }

    protected function createWebSocketsServer(string $action): WsServer
    {
        $wServer = new WsServer(app($action));

        $wServer->enableKeepAlive(app(LoopInterface::class));

        return $wServer;
    }
}

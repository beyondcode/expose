<?php

namespace App\Http;

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
        return new Route($uri, ['_controller' => app($action)], [], [], null, [], [$method], $condition);
    }
}

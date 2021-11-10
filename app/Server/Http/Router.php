<?php

namespace App\Server\Http;

use App\Http\QueryParameters;
use function GuzzleHttp\Psr7\build_query;
use function GuzzleHttp\Psr7\parse_query;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\CloseResponseTrait;
use Ratchet\Http\HttpServerInterface;
use Ratchet\Http\NoOpHttpServerController;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;

class Router implements HttpServerInterface
{
    use CloseResponseTrait;

    /**
     * @var UrlMatcher
     */
    protected $_matcher;

    private $_noopController;

    public function __construct(UrlMatcher $matcher)
    {
        $this->_matcher = $matcher;
        $this->_noopController = new NoOpHttpServerController;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \UnexpectedValueException If a controller is not \Ratchet\Http\HttpServerInterface
     */
    public function onOpen(ConnectionInterface $conn, RequestInterface $request = null)
    {
        if (null === $request) {
            throw new \UnexpectedValueException('$request can not be null');
        }

        $conn->controller = $this->_noopController;

        $uri = $request->getUri();

        $context = $this->_matcher->getContext();
        $context->setMethod($request->getMethod());
        $context->setHost($uri->getHost());

        $symfonyRequest = $this->createSymfonyRequest($request);

        try {
            $route = $this->_matcher->matchRequest($symfonyRequest);
        } catch (MethodNotAllowedException $nae) {
            return $this->close($conn, 405, ['Allow' => $nae->getAllowedMethods()]);
        } catch (ResourceNotFoundException $nfe) {
            return $this->close($conn, 404);
        }

        if (is_string($route['_controller']) && class_exists($route['_controller'])) {
            $route['_controller'] = new $route['_controller'];
        }

        if (! ($route['_controller'] instanceof HttpServerInterface)) {
            throw new \UnexpectedValueException('All routes must implement Ratchet\Http\HttpServerInterface');
        }

        $parameters = [];
        foreach ($route as $key => $value) {
            if ((is_string($key)) && ('_' !== substr($key, 0, 1))) {
                $parameters[$key] = $value;
            }
        }
        $parameters = array_merge($parameters, parse_query($uri->getQuery() ?: ''));

        $request = $request->withUri($uri->withQuery(build_query($parameters)));

        $conn->controller = $route['_controller'];
        $conn->controller->onOpen($conn, $request);
    }

    /**
     * {@inheritdoc}
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        $from->controller->onMessage($from, $msg);
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(ConnectionInterface $conn)
    {
        if (isset($conn->controller)) {
            $conn->controller->onClose($conn);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        if (isset($conn->controller)) {
            $conn->controller->onError($conn, $e);
        }
    }

    protected function createSymfonyRequest(RequestInterface $request)
    {
        $serverRequest = (new ServerRequest(
            $request->getMethod(),
            $request->getUri(),
            $request->getHeaders(),
            $request->getBody(),
            $request->getProtocolVersion()
        ))->withQueryParams(QueryParameters::create($request)->all());

        return (new HttpFoundationFactory())->createRequest($serverRequest);
    }
}

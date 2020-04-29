<?php

namespace App\Client\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use App\WebSockets\Socket;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use Ratchet\ConnectionInterface;
use function GuzzleHttp\Psr7\str;
use Psr\Http\Message\RequestInterface;

class PushLogsToDashboardController extends Controller
{

    public function onOpen(ConnectionInterface $connection, RequestInterface $request = null)
    {
        $connection->contentLength = $this->findContentLength($request->getHeaders());

        $connection->requestBuffer = (string) $request->getBody();

        $this->checkContentLength($connection);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $from->requestBuffer .= $msg;

        $this->checkContentLength($from);
    }

    protected function findContentLength(array $headers): int
    {
        return Collection::make($headers)->first(function ($values, $header) {
                return strtolower($header) === 'content-length';
            })[0] ?? 0;
    }

    protected function checkContentLength(ConnectionInterface $connection)
    {
        if (strlen($connection->requestBuffer) === $connection->contentLength) {
            try {
                /*
                 * This is the post payload from our PHPUnit tests.
                 * Send it to the connected connections.
                 */
                foreach (Socket::$connections as $webSocketConnection) {
                    $webSocketConnection->send($connection->requestBuffer);
                }

                $connection->send(str(new Response(200)));
            } catch (Exception $e) {
                $connection->send(str(new Response(500, [], $e->getMessage())));
            }

            $connection->close();

            unset($connection->requestBuffer);
            unset($connection->contentLength);
        }
    }
}

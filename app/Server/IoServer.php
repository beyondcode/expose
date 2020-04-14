<?php

namespace App\Server;


use App\Server\Connections\IoConnection;

class IoServer extends \Ratchet\Server\IoServer
{
    public function handleConnect($conn) {
        $conn->decor = new IoConnection($conn);
        $conn->decor->resourceId = (int)$conn->stream;

        $uri = $conn->getRemoteAddress();
        $conn->decor->remoteAddress = trim(
            parse_url((strpos($uri, '://') === false ? 'tcp://' : '') . $uri, PHP_URL_HOST),
            '[]'
        );

        $this->app->onOpen($conn->decor);

        $conn->on('data', function ($data) use ($conn) {
            $this->handleData($data, $conn);
        });
        $conn->on('close', function () use ($conn) {
            $this->handleEnd($conn);
        });
        $conn->on('error', function (\Exception $e) use ($conn) {
            $this->handleError($e, $conn);
        });
    }
}

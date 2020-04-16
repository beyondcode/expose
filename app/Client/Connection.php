<?php

namespace App\Client;

use Throwable;
use React\Socket\ConnectionInterface;

class Connection
{
    /** @var ConnectionInterface */
    protected $socket;

    /** @var ProxyManager */
    protected $proxyManager;

    public static function create(ConnectionInterface $socketConnection, ProxyManager $proxyManager)
    {
        return new static($socketConnection, $proxyManager);
    }

    public function __construct(ConnectionInterface $socketConnection, ProxyManager $proxyManager)
    {
        $this->socket = $socketConnection;
        $this->proxyManager = $proxyManager;

        $this->socket->on('data', function ($data) {
            $jsonStrings = explode("||", $data);

            $decodedEntries = [];

            foreach ($jsonStrings as $jsonString) {
                try {
                    $decodedJsonObject = json_decode($jsonString);
                    if (is_object($decodedJsonObject)) {
                        $decodedEntries[] = $decodedJsonObject;
                    }
                } catch (Throwable $e) {
                    // Ignore payload
                }
            }

            foreach ($decodedEntries as $decodedEntry) {
                if (method_exists($this, $decodedEntry->event ?? '')) {
                    $this->socket->emit($decodedEntry->event, [$decodedEntry]);

                    call_user_func([$this, $decodedEntry->event], $decodedEntry);
                }
            }
        });
    }

    public function authenticated($data)
    {
        $this->socket->_id = $data->client_id;

        $this->createProxy($data);
    }

    public function createProxy($data)
    {
        $this->proxyManager->createProxy($this->socket, $data);
    }

    public function authenticate(string $sharedHost, string $subdomain)
    {
        $this->socket->write(json_encode([
            'event' => 'authenticate',
            'data' => [
                'host' => $sharedHost,
                'subdomain' => empty($subdomain) ? null : $subdomain,
            ],
        ]));
    }

    public function ping()
    {
        $this->socket->write(json_encode([
            'event' => 'pong',
        ]));
    }
}

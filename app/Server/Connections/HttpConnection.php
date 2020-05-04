<?php

namespace App\Server\Connections;

use Evenement\EventEmitterTrait;
use Ratchet\ConnectionInterface;

class HttpConnection
{
    use EventEmitterTrait;

    /** @var ConnectionInterface */
    public $socket;

    public function __construct(ConnectionInterface $socket)
    {
        $this->socket = $socket;
    }

    public function send($data)
    {
        $this->emit('data', [$data]);

        $this->socket->send($data);
    }

    public function close()
    {
        $this->emit('close');

        $this->socket->close();
    }
}

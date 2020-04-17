<?php

namespace App\Server\Connections;

use Ratchet\ConnectionInterface;
use React\Socket\ConnectionInterface as ReactConn;

class IoConnection implements ConnectionInterface {
    /**
     * @var \React\Socket\ConnectionInterface
     */
    protected $conn;

    /**
     * @param \React\Socket\ConnectionInterface $conn
     */
    public function __construct(ReactConn $conn) {
        $this->conn = $conn;
    }

    /**
     * @return ReactConn
     */
    public function getConnection(): ReactConn
    {
        return $this->conn;
    }

    /**
     * {@inheritdoc}
     */
    public function send($data) {
        $this->conn->write($data);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function close() {
        $this->conn->end();
    }
}

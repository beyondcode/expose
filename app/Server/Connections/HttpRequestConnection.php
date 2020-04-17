<?php

namespace App\Server\Connections;

use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Ratchet\ConnectionInterface;
use function GuzzleHttp\Psr7\parse_request;

class HttpRequestConnection implements ConnectionInterface
{
    /** @var IoConnection */
    protected $connection;

    public static function wrap(ConnectionInterface $connection, $message)
    {
        return new static($connection, $message);
    }

    public function __construct(ConnectionInterface $connection, $message)
    {
        $this->connection = $connection;

        if (! isset($this->connection->buffer)) {
            $this->connection->buffer = '';
        }

        $this->connection->buffer .= $message;
    }

    public function getRequest(): Request
    {
        return parse_request($this->connection->buffer);
    }

    protected function getContentLength(): ?int
    {
        return Arr::first($this->getRequest()->getHeader('Content-Length'));
    }

    public function hasBufferedAllData()
    {
        return is_null($this->getContentLength()) || strlen(Str::after($this->connection->buffer, "\r\n\r\n")) === $this->getContentLength();
    }

    public function getConnection()
    {
        return $this->connection->getConnection();
    }

    public function __get($key)
    {
        return $this->connection->$key;
    }

    public function __set($key, $value)
    {
        return $this->connection->$key = $value;
    }

    public function __unset($key)
    {
        unset($this->connection->$key);
    }

    public function send($data)
    {
        return $this->connection->send($data);
    }

    public function close()
    {
        return $this->connection->close();
    }
}

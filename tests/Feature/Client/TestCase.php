<?php

namespace Tests\Feature\Client;

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use function Clue\React\Block\await;

abstract class TestCase extends \Tests\TestCase
{
    const AWAIT_TIMEOUT = 1.0;

    /** @var LoopInterface */
    protected $loop;

    public function setUp(): void
    {
        parent::setUp();

        $this->app->bind(ConsoleOutputInterface::class, function() {
            return new ConsoleOutput();
        });

        /** @var LoopInterface $loop */
        $this->loop = $this->app->make(LoopInterface::class);
    }

    protected function await(PromiseInterface $promise, LoopInterface $loop = null, $timeout = null)
    {
        return await($promise, $loop ?? $this->loop, $timeout ?? static::AWAIT_TIMEOUT);
    }
}

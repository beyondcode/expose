<?php

namespace Tests\Feature;

use Clue\React\SQLite\DatabaseInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

use function Clue\React\Block\await;

abstract class TestCase extends \Tests\TestCase
{
    const AWAIT_TIMEOUT = 5.0;

    /** @var LoopInterface */
    protected $loop;

    public function setUp(): void
    {
        parent::setUp();

        $this->app->bind(ConsoleOutputInterface::class, function () {
            return new ConsoleOutput();
        });

        /** @var LoopInterface $loop */
        $this->loop = $this->app->make(LoopInterface::class);
    }

    protected function await(PromiseInterface $promise, LoopInterface $loop = null, $timeout = null)
    {
        return await($promise, $loop ?? $this->loop, $timeout ?? static::AWAIT_TIMEOUT);
    }

    protected function assertDatabaseHasResults($query)
    {
        $database = app(DatabaseInterface::class);

        $result = $this->await($database->query($query));

        $this->assertGreaterThanOrEqual(1, count($result->rows));
    }

    protected function assertDatabaseHasNoResults($query)
    {
        $database = app(DatabaseInterface::class);

        $result = $this->await($database->query($query));

        $this->assertEmpty($result->rows);
    }
}

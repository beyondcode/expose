<?php

namespace Tests;

use Clue\React\SQLite\DatabaseInterface;
use LaravelZero\Framework\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
}

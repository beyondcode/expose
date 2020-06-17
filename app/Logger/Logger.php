<?php

namespace App\Logger;

use Illuminate\Console\Concerns\InteractsWithIO;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

class Logger
{
    use InteractsWithIO;

    /** @var ConsoleOutputInterface */
    protected $output;

    public function __construct(ConsoleOutputInterface $consoleOutput)
    {
        $this->output = $consoleOutput;
    }
}

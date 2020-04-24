<?php

namespace App\Logger;

use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

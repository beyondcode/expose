<?php

namespace App\Commands;

use App\Logger\CliRequestLogger;
use Illuminate\Console\Parser;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

abstract class ServerAwareCommand extends Command
{
    public function __construct()
    {
        parent::__construct();

        $inheritedSignature = '{--server=default} {--server-host=} {--server-port=}';

        $this->getDefinition()->addOptions(Parser::parse($inheritedSignature)[2]);

        $this->configureConnectionLogger();
    }

    protected function configureConnectionLogger()
    {
        app()->bind(CliRequestLogger::class, function () {
            return new CliRequestLogger(new ConsoleOutput());
        });

        return $this;
    }

    protected function getServerHost()
    {
        return $this->option('server-host') ?? config('expose.servers.'.$this->option('server').'.host', 'localhost');
    }

    protected function getServerPort()
    {
        return $this->option('server-port') ?? config('expose.servers.'.$this->option('server').'.port', 8080);
    }
}

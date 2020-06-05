<?php

namespace App\Commands;

use App\Server\Factory;
use LaravelZero\Framework\Commands\Command;
use React\EventLoop\LoopInterface;

class ServeCommand extends Command
{
    protected $signature = 'serve {hostname=localhost} {host=0.0.0.0}  {--validateAuthTokens} {--port=8080}';

    protected $description = 'Start the shaft server';

    public function handle()
    {
        /** @var LoopInterface $loop */
        $loop = app(LoopInterface::class);

        $loop->futureTick(function () {
            $this->info("Expose server running on port ".$this->option('port').".");
        });

        $validateAuthTokens = config('expose.admin.validate_auth_tokens');

        if ($this->option('validateAuthTokens')) {
            $validateAuthTokens = true;
        }

        (new Factory())
            ->setLoop($loop)
            ->setHost($this->argument('host'))
            ->setPort($this->option('port'))
            ->setHostname($this->argument('hostname'))
            ->validateAuthTokens($validateAuthTokens)
            ->createServer()
            ->run();
    }
}

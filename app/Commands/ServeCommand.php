<?php

namespace App\Commands;

use App\Server\Factory;
use React\EventLoop\LoopInterface;

class ServeCommand extends Command
{
    protected $signature = 'serve {hostname=localhost} {host=0.0.0.0}  {--validateAuthTokens} {--port=8080}';

    protected $description = 'Start the expose server';

    public function handle()
    {
        /** @var LoopInterface $loop */
        $loop = app(LoopInterface::class);

        $loop->futureTick(function () {
            $this->info('Expose server running on port '.$this->option('port').'.');
        });

        if ($this->option('validateAuthTokens') === true) {
            $validateAuthTokens = true;
        } else {
            $validateAuthTokens = config('expose.admin.validate_auth_tokens');
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

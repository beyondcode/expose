<?php

namespace App\Commands;

use App\Server\Factory;
use InvalidArgumentException;
use LaravelZero\Framework\Commands\Command;
use React\EventLoop\LoopInterface;

class ServeCommand extends Command
{
    protected $signature = 'serve {hostname=localhost} {host=0.0.0.0}  {--validateAuthTokens} {--port=8080} {--config=}';

    protected $description = 'Start the expose server';

    protected function loadConfiguration(string $configFile)
    {
        $configFile = realpath($configFile);

        throw_if(! file_exists($configFile), new InvalidArgumentException("Invalid config file {$configFile}"));

        $localConfig = require $configFile;
        config()->set('expose', $localConfig);
    }

    public function handle()
    {
        /** @var LoopInterface $loop */
        $loop = app(LoopInterface::class);

        if ($this->option('config')) {
            $this->loadConfiguration($this->option('config'));
        }

        $loop->futureTick(function () {
            $this->info('Expose server running on port '.$this->option('port').'.');
        });

        $validateAuthTokens = config('expose.admin.validate_auth_tokens');

        if ($this->option('validateAuthTokens') === true) {
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

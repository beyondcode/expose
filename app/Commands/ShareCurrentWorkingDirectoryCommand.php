<?php

namespace App\Commands;

use Illuminate\Console\Command;

class ShareCurrentWorkingDirectoryCommand extends ShareCommand
{
    protected $signature = 'share-cwd {host?} {--subdomain=} {--auth=}';

    public function handle()
    {
        $this->input->setArgument('host', basename(getcwd()).'.'.$this->detectTld());

        $this->input->setOption('subdomain', basename(getcwd()));

        parent::handle();
    }

    protected function detectTld(): string
    {
        $valetConfigFile = $_SERVER['HOME'] . DIRECTORY_SEPARATOR . '.config' . DIRECTORY_SEPARATOR . 'valet' . DIRECTORY_SEPARATOR . 'config.json';

        if (file_exists($valetConfigFile)) {
            $valetConfig = json_decode(file_get_contents($valetConfigFile));
            return $valetConfig->tld;
        }

        return 'test';
    }
}

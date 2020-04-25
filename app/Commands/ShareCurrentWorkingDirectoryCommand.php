<?php

namespace App\Commands;

use Illuminate\Console\Command;

class ShareCurrentWorkingDirectoryCommand extends ShareCommand
{
    protected $signature = 'share-cwd {host?} {--subdomain=} {--auth=}';

    public function handle()
    {
        $this->input->setArgument('host', basename(getcwd()).'.test');

        $this->input->setOption('subdomain', basename(getcwd()));

        parent::handle();
    }
}

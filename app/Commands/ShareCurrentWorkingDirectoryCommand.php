<?php

namespace App\Commands;

class ShareCurrentWorkingDirectoryCommand extends ShareCommand
{
    protected $signature = 'share-cwd {host?} {--subdomain=} {--auth=}';

    public function handle()
    {
        $this->input->setArgument('host', $this->host());

        if (! $this->option('subdomain')) {
            $this->input->setOption('subdomain', $this->subdomain());
        }

        parent::handle();
    }

    protected function host(): string
    {
        $host = $this->base().'.'.$this->tld();

        if (is_file($this->pathFromHome('.config', 'valet', 'Certificates', $host.'.crt'))) {
            return 'https://'.$host;
        }

        return $host;
    }

    protected function tld(): string
    {
        $file = $this->pathFromHome('.config', 'valet', 'config.json');

        if (is_file($file)) {
            return $this->fileRead($file, 'json')->id;
        }

        return config('expose.default_tld', 'test');
    }

    protected function subdomain(): string
    {
        return str_replace('.', '_', $this->base());
    }

    protected function base(): string
    {
        return basename(getcwd());
    }
}

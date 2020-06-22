<?php

namespace App\Commands;

class ShareCurrentWorkingDirectoryCommand extends ShareCommand
{
    protected $signature = 'share-cwd {host?} {--subdomain=} {--auth=}';

    public function handle()
    {
        $host = $this->prepareSharedHost(basename(getcwd()).'.'.$this->detectTld());

        $this->input->setArgument('host', $host);

        if (! $this->hasOption('subdomain')) {
            $subdomain = str_replace('.', '_', basename(getcwd()));
            $this->input->setOption('subdomain', $subdomain);
        }

        parent::handle();
    }

    protected function detectTld(): string
    {
        $valetConfigFile = ($_SERVER['HOME'] ?? $_SERVER['USERPROFILE']).DIRECTORY_SEPARATOR.'.config'.DIRECTORY_SEPARATOR.'valet'.DIRECTORY_SEPARATOR.'config.json';

        if (file_exists($valetConfigFile)) {
            $valetConfig = json_decode(file_get_contents($valetConfigFile));

            return $valetConfig->tld;
        }

        return config('expose.default_tld', 'test');
    }

    protected function prepareSharedHost($host): string
    {
        $certificateFile = ($_SERVER['HOME'] ?? $_SERVER['USERPROFILE']).DIRECTORY_SEPARATOR.'.config'.DIRECTORY_SEPARATOR.'valet'.DIRECTORY_SEPARATOR.'Certificates'.DIRECTORY_SEPARATOR.$host.'.crt';

        if (file_exists($certificateFile)) {
            return 'https://'.$host;
        }

        return $host;
    }
}

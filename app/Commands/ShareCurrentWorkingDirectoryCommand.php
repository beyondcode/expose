<?php

namespace App\Commands;

class ShareCurrentWorkingDirectoryCommand extends ShareCommand
{
    protected $signature = 'share-cwd {host?} {--subdomain=} {--auth=} {--server-host=} {--server-port=}';

    public function handle()
    {
        $subdomain = $this->detectName();
        $host = $this->prepareSharedHost($subdomain.'.'.$this->detectTld());

        $this->input->setArgument('host', $host);

        if (! $this->option('subdomain')) {
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

    protected function detectName(): string
    {
        $projectPath = getcwd();
        $valetSitesPath = ($_SERVER['HOME'] ?? $_SERVER['USERPROFILE']).DIRECTORY_SEPARATOR.'.config'.DIRECTORY_SEPARATOR.'valet'.DIRECTORY_SEPARATOR.'Sites';

        if (is_dir($valetSitesPath)) {
            $site = collect(scandir($valetSitesPath))
            ->skip(2)
            ->map(function ($site) use ($valetSitesPath) {
                return $valetSitesPath.DIRECTORY_SEPARATOR.$site;
            })->mapWithKeys(function ($site) {
                return [$site => readlink($site)];
            })->filter(function ($sourcePath) use ($projectPath) {
                return $sourcePath === $projectPath;
            })
            ->keys()
            ->first();

            if ($site) {
                $projectPath = $site;
            }
        }

        return str_replace('.', '-', basename($projectPath));
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

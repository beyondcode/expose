<?php

namespace App\Commands;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    protected $signature = 'publish';

    protected $description = 'Publish the expose configuration file';

    public function handle()
    {
        $configFile = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'],
            '.expose',
            'config.php'
        ]);

        if (file_exists($configFile)) {
            $this->error('Expose configuration file already exists at '.$configFile);
            return;
        }

        file_put_contents($configFile, file_get_contents(base_path('config/expose.php')));

        $this->info('Published expose configuration file to: ' . $configFile);
    }
}

<?php

namespace App\Commands;

class PublishCommand extends Command
{
    protected $signature = 'publish {--force}';

    protected $description = 'Publish the expose configuration file';

    public function handle()
    {
        $file = $this->pathFromHome('.expose', 'config.php');

        if (! $this->option('force') && is_file($file)) {
            return $this->error('Expose configuration file already exists at '.$file);
        }

        $this->fileWrite($file, $this->fileRead(base_path('config/expose.php')));

        $this->info('Published expose configuration file to: '.$file);
    }
}

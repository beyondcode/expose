<?php

namespace App\Providers;

use App\Logger\CliRequestLogger;
use App\Logger\RequestLogger;
use Clue\React\Buzz\Browser;
use Illuminate\Support\ServiceProvider;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        //
    }

    public function register()
    {
        $this->loadConfigurationFile();

        $this->app->singleton(LoopInterface::class, function () {
            return LoopFactory::create();
        });

        $this->app->singleton(RequestLogger::class, function ($app) {
            return new RequestLogger($app->make(Browser::class), $app->make(CliRequestLogger::class));
        });
    }

    protected function loadConfigurationFile()
    {
        $localConfigFile = getcwd() . DIRECTORY_SEPARATOR . '.expose.php';

        if (file_exists($localConfigFile)) {
            config()->set('expose', require_once $localConfigFile);
            return;
        }


        $configFile = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'],
            '.expose',
            'config.php'
        ]);

        if (file_exists($configFile)) {
            config()->set('expose', require_once $configFile);
        }
    }
}

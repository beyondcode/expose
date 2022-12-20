<?php

namespace App\Providers;

use App\Logger\CliRequestLogger;
use App\Logger\RequestLogger;
use Illuminate\Support\ServiceProvider;
use Laminas\Uri\Uri;
use Laminas\Uri\UriFactory;
use React\EventLoop\Factory as LoopFactory;
use React\EventLoop\LoopInterface;
use React\Http\Browser;

class AppServiceProvider extends ServiceProvider
{
    public function boot()
    {
        UriFactory::registerScheme('capacitor', Uri::class);
        UriFactory::registerScheme('chrome-extension', Uri::class);
    }

    public function register()
    {
        $this->loadConfigurationFile();

        $this->setMemoryLimit();

        $this->app->singleton(LoopInterface::class, function () {
            return LoopFactory::create();
        });

        $this->app->bind(Browser::class, function ($app) {
            return new Browser($app->make(LoopInterface::class));
        });

        $this->app->singleton(RequestLogger::class, function ($app) {
            return new RequestLogger($app->make(Browser::class), $app->make(CliRequestLogger::class));
        });
    }

    protected function loadConfigurationFile()
    {
        $builtInConfig = config('expose');

        $keyServerVariable = 'EXPOSE_CONFIG_FILE';
        if (array_key_exists($keyServerVariable, $_SERVER) && is_string($_SERVER[$keyServerVariable]) && file_exists($_SERVER[$keyServerVariable])) {
            $localConfig = require $_SERVER[$keyServerVariable];
            config()->set('expose', array_merge($builtInConfig, $localConfig));

            return;
        }

        $localConfigFile = getcwd().DIRECTORY_SEPARATOR.'.expose.php';

        if (file_exists($localConfigFile)) {
            $localConfig = require $localConfigFile;
            config()->set('expose', array_merge($builtInConfig, $localConfig));

            return;
        }

        $configFile = implode(DIRECTORY_SEPARATOR, [
            $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? __DIR__,
            '.expose',
            'config.php',
        ]);

        if (file_exists($configFile)) {
            $globalConfig = require $configFile;
            config()->set('expose', array_merge($builtInConfig, $globalConfig));
        }
    }

    protected function setMemoryLimit()
    {
        ini_set('memory_limit', config()->get('expose.memory_limit', '128M'));
    }
}

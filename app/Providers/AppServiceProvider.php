<?php

namespace App\Providers;

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
        $this->app->singleton(LoopInterface::class, function () {
            return LoopFactory::create();
        });

        $this->app->singleton(RequestLogger::class, function () {
            $browser = new Browser(app(LoopInterface::class));
            return new RequestLogger($browser);
        });
    }
}

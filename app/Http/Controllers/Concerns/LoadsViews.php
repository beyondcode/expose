<?php

namespace App\Http\Controllers\Concerns;

use Twig\Environment;
use Twig\Loader\ArrayLoader;
use function GuzzleHttp\Psr7\stream_for;

trait LoadsViews
{
    protected function getView(string $view, array $data = [])
    {
        $templatePath = implode(DIRECTORY_SEPARATOR, explode('.', $view));

        $twig = new Environment(
            new ArrayLoader([
                'app' => file_get_contents(base_path('resources/views/server/layouts/app.twig')),
                'template' => file_get_contents(base_path('resources/views/'.$templatePath.'.twig')),
            ])
        );

        return stream_for($twig->render('template', $data));
    }
}

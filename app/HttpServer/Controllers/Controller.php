<?php

namespace App\HttpServer\Controllers;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServerInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use function GuzzleHttp\Psr7\stream_for;

abstract class Controller implements HttpServerInterface
{
    public function onClose(ConnectionInterface $connection)
    {
        unset($connection->requestBuffer);
        unset($connection->contentLength);
        unset($connection->request);
    }

    public function onError(ConnectionInterface $connection, Exception $e)
    {
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
    }

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

<?php

namespace App\Client\Http\Controllers;

use App\Http\Controllers\Controller;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Ratchet\ConnectionInterface;

class FileController extends Controller
{
    public function handle(Request $request, ConnectionInterface $httpConnection)
    {
        $file = $request->get('path');

        $filePath = app()->basePath().'/public/'.$file;

        if (! file_exists($filePath)) {
            $httpConnection->send(Message::toString(new Response(
                404,
                ['Content-Type' => 'text/html'],
                'File not found'
            )));

            return;
        }

        $file = file_get_contents($filePath);
        $contentType = mime_content_type($filePath);

        if (str($filePath)->endsWith('.css')) {
            $contentType = 'text/css';
        }

        $httpConnection->send(Message::toString(new Response(
            200,
            ['Content-Type' => $contentType],
            $file
        )));
    }
}

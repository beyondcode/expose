<?php

namespace App\Client\Fileserver;

use App\Http\Controllers\Concerns\LoadsViews;
use App\Http\QueryParameters;
use GuzzleHttp\Psr7\ServerRequest;
use Illuminate\Http\Request;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Http\Message\Response;
use React\Stream\ReadableResourceStream;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\Iterator\FilenameFilterIterator;

class ConnectionHandler
{
    use LoadsViews;

    /** @var string */
    protected $rootFolder;

    /** @var string */
    protected $name;

    /** @var LoopInterface */
    protected $loop;

    public function __construct(string $rootFolder, string $name, LoopInterface $loop)
    {
        $this->rootFolder = $rootFolder;
        $this->name = $name;
        $this->loop = $loop;
    }

    public function handle(ServerRequestInterface $request)
    {
        $request = $this->createLaravelRequest($request);
        $targetPath = realpath($this->rootFolder.DIRECTORY_SEPARATOR.$request->path());

        if (! $this->isValidTarget($targetPath)) {
            return new Response(404);
        }

        if (is_dir($targetPath)) {
            // Directory listing
            $directoryContent = Finder::create()
                ->depth(0)
                ->sort(function ($a, $b) {
                    return strcmp(strtolower($a->getRealpath()), strtolower($b->getRealpath()));
                })
                ->in($targetPath);

            if ($this->name !== '') {
                $directoryContent->name($this->name);
            }

            $parentPath = explode('/', $request->path());
            array_pop($parentPath);
            $parentPath = implode('/', $parentPath);

            return new Response(
                200,
                ['Content-Type' => 'text/html'],
                $this->getView(null, 'client.fileserver', [
                    'currentPath' => $request->path(),
                    'parentPath' => $parentPath,
                    'directory' => $targetPath,
                    'directoryContent' => $directoryContent,
                ])
            );
        }

        if (is_file($targetPath)) {
            return new Response(
                200,
                ['Content-Type' => mime_content_type($targetPath)],
                new ReadableResourceStream(fopen($targetPath, 'r'), $this->loop)
            );
        }
    }

    protected function isValidTarget(string $targetPath): bool
    {
        if (! file_exists($targetPath)) {
            return false;
        }

        if ($this->name !== '') {
            $filter = new class(basename($targetPath), [$this->name]) extends FilenameFilterIterator
            {
                protected $filename;

                public function __construct(string $filename, array $matchPatterns)
                {
                    $this->filename = $filename;

                    foreach ($matchPatterns as $pattern) {
                        $this->matchRegexps[] = $this->toRegex($pattern);
                    }
                }

                public function accept(): bool
                {
                    return $this->isAccepted($this->filename);
                }
            };

            return $filter->accept();
        }

        return true;
    }

    protected function createLaravelRequest(ServerRequestInterface $request): Request
    {
        try {
            parse_str($request->getBody(), $bodyParameters);
        } catch (\Throwable $e) {
            $bodyParameters = [];
        }

        $serverRequest = (new ServerRequest(
            $request->getMethod(),
            $request->getUri(),
            $request->getHeaders(),
            $request->getBody(),
            $request->getProtocolVersion(),
        ))
            ->withQueryParams(QueryParameters::create($request)->all())
            ->withParsedBody($bodyParameters);

        return Request::createFromBase((new HttpFoundationFactory)->createRequest($serverRequest));
    }
}

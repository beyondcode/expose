<?php

namespace App\Commands;

use Illuminate\Console\Command as IlluminateCommand;

abstract class Command extends IlluminateCommand
{
    protected function serverHome(): string
    {
        return $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? '';
    }

    protected function pathFromHome(): string
    {
        return implode(DIRECTORY_SEPARATOR, array_merge([$this->serverHome()], func_get_args()));
    }

    protected function createDir(string $dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    protected function fileRead(string $file, ?string $format = null)
    {
        $contents = file_get_contents($file);

        if ($format === 'json') {
            return json_decode($contents);
        }

        return $contents;
    }

    protected function fileWrite(string $file, string $contents)
    {
        $this->createDir(dirname($file));

        file_put_contents($file, $contents, LOCK_EX);
    }
}

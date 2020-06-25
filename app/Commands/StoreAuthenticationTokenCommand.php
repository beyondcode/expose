<?php

namespace App\Commands;

use App\Client\Support\TokenNodeVisitor;
use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;

class StoreAuthenticationTokenCommand extends Command
{
    protected $signature = 'token {token?}';

    protected $description = 'Set or retrieve the authentication token to use with expose.';

    public function handle()
    {
        if ($this->argument('token')) {
            return $this->fromToken($this->argument('token'));
        }

        if ($token = config('expose.auth_token')) {
            $this->info('Current authentication token: '.$token);
        } else {
            $this->info('There is no authentication token specified.');
        }
    }

    protected function fromToken(string $token)
    {
        $this->info('Setting the expose authentication token to "'.$token.'"');

        $file = $this->pathFromHome('.expose', 'config.php');

        $this->fileWrite($file, $this->modifyConfiguration($this->config($file), $token));
    }

    protected function config(string $file): string
    {
        if (! is_file($file)) {
            $file = base_path('config/expose.php');
        }

        return $this->fileRead($file);
    }

    protected function modifyConfiguration(string $config, string $token): string
    {
        $lexer = new Emulative([
            'usedAttributes' => [
                'comments',
                'startLine', 'endLine',
                'startTokenPos', 'endTokenPos',
            ],
        ]);

        $parser = new Php7($lexer);

        $oldStmts = $parser->parse($config);
        $oldTokens = $lexer->getTokens();

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new CloningVisitor());
        $newStmts = $nodeTraverser->traverse($oldStmts);

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new TokenNodeVisitor($token));

        $newStmts = $nodeTraverser->traverse($newStmts);

        return (new Standard())->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
    }
}

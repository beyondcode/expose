<?php

namespace App\Commands;

use App\Client\Support\DefaultServerNodeVisitor;
use App\Client\Support\InsertDefaultServerNodeVisitor;
use Illuminate\Console\Command;
use PhpParser\Lexer\Emulative;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;

class SetDefaultServerCommand extends Command
{
    protected $signature = 'default-server {server?}';

    protected $description = 'Set or retrieve the default server to use with Expose.';

    public function handle()
    {
        $server = $this->argument('server');
        if (! is_null($server)) {
            $this->info('Setting the Expose default server to "'.$server.'"');

            $configFile = implode(DIRECTORY_SEPARATOR, [
                $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'],
                '.expose',
                'config.php',
            ]);

            if (! file_exists($configFile)) {
                @mkdir(dirname($configFile), 0777, true);
                $updatedConfigFile = $this->modifyConfigurationFile(base_path('config/expose.php'), $server);
            } else {
                $updatedConfigFile = $this->modifyConfigurationFile($configFile, $server);
            }

            file_put_contents($configFile, $updatedConfigFile);

            return;
        }

        if (is_null($server = config('expose.default_server'))) {
            $this->info('There is no default server specified.');
        } else {
            $this->info('Current default server: '.$server);
        }
    }

    protected function modifyConfigurationFile(string $configFile, string $server)
    {
        $lexer = new Emulative([
            'usedAttributes' => [
                'comments',
                'startLine', 'endLine',
                'startTokenPos', 'endTokenPos',
            ],
        ]);
        $parser = new Php7($lexer);

        $oldStmts = $parser->parse(file_get_contents($configFile));
        $oldTokens = $lexer->getTokens();

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new CloningVisitor());
        $newStmts = $nodeTraverser->traverse($oldStmts);

        $nodeFinder = new NodeFinder;

        $defaultServerNode = $nodeFinder->findFirst($newStmts, function (Node $node) {
            return $node instanceof Node\Expr\ArrayItem && $node->key && $node->key->value === 'default_server';
        });

        if (is_null($defaultServerNode)) {
            $nodeTraverser = new NodeTraverser;
            $nodeTraverser->addVisitor(new InsertDefaultServerNodeVisitor());
            $newStmts = $nodeTraverser->traverse($newStmts);
        }

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new DefaultServerNodeVisitor($server));

        $newStmts = $nodeTraverser->traverse($newStmts);

        $prettyPrinter = new Standard();

        return $prettyPrinter->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
    }
}

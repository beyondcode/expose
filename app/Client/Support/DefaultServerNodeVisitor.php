<?php

namespace App\Client\Support;

use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

class DefaultServerNodeVisitor extends NodeVisitorAbstract
{
    /** @var string */
    protected $server;

    public function __construct(string $server)
    {
        $this->server = $server;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Expr\ArrayItem && $node->key && $node->key->value === 'default_server') {
            $node->value = new String_($this->server);

            return $node;
        }
    }
}

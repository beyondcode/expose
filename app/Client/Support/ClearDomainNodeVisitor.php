<?php

namespace App\Client\Support;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PhpParser\NodeVisitorAbstract;

class ClearDomainNodeVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Expr\ArrayItem && $node->key && $node->key->value === 'default_domain') {
            $node->value = new ConstFetch(
                new Name('null')
            );

            return $node;
        }
    }
}

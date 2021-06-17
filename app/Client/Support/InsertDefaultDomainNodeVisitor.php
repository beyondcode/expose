<?php

namespace App\Client\Support;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Name;
use PhpParser\NodeVisitorAbstract;

class InsertDefaultDomainNodeVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Expr\ArrayItem && $node->key && $node->key->value === 'auth_token') {
            $defaultDomainNode = new Node\Expr\ArrayItem(
                new ConstFetch(
                    new Name('null')
                ),
                new Node\Scalar\String_('default_domain')
            );

            return [
                $node,
                $defaultDomainNode,
            ];
        }
    }
}

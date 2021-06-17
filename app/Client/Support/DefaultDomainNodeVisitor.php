<?php

namespace App\Client\Support;

use PhpParser\Node;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;

class DefaultDomainNodeVisitor extends NodeVisitorAbstract
{
    /** @var string */
    protected $domain;

    public function __construct(string $domain)
    {
        $this->domain = $domain;
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Expr\ArrayItem && $node->key && $node->key->value === 'default_domain') {
            $node->value = new String_($this->domain);

            return $node;
        }
    }
}

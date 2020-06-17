<?php

namespace App\Client\Support;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class TokenNodeVisitor extends NodeVisitorAbstract
{
    /** @var string */
    protected $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Node\Expr\ArrayItem && $node->key && $node->key->value === 'auth_token') {
            $node->value->value = $this->token;

            return $node;
        }
    }
}

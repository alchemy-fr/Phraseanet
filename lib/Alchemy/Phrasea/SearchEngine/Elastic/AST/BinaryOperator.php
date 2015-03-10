<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

abstract class BinaryOperator extends Node
{
    protected $left;
    protected $right;
    protected $operator = 'BIN_OP';

    public function __construct(Node $left, Node $right)
    {
        $this->left = $left;
        $this->right = $right;
    }

    public function __toString()
    {
        return sprintf('(%s %s %s)', $this->left, $this->operator, $this->right);
    }

    public function getTermNodes()
    {
        return array_merge(
            $this->left->getTermNodes(),
            $this->right->getTermNodes()
        );
    }
}

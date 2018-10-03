<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST\Boolean;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class OrExpression extends BinaryExpression
{
    protected $operator = 'OR';

    public function buildQuery(QueryContext $context)
    {
        $left  = $this->left->buildQuery($context);
        $right = $this->right->buildQuery($context);

        return [
            'bool' => [
                'should' => array($left, $right)
            ]
        ];
    }
}

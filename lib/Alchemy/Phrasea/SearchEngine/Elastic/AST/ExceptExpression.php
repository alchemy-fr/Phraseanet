<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class ExceptExpression extends BinaryOperator
{
    protected $operator = 'EXCEPT';

    public function buildQuery(QueryContext $context)
    {
        $left  = $this->left->buildQuery($context);
        $right = $this->right->buildQuery($context);

        return [
            'bool' => [
                'must' => $left,
                'must_not' => $right
            ]
        ];
    }
}

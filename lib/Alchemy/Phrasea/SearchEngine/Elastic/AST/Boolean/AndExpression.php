<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST\Boolean;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class AndExpression extends BinaryExpression
{
    protected $operator = 'AND';

    public function buildQuery(QueryContext $context)
    {
        $left  = $this->left->buildQuery($context);
        $right = $this->right->buildQuery($context);

        return [
            'bool' => [
                'must' => [$left, $right]
            ]
        ];
    }
}

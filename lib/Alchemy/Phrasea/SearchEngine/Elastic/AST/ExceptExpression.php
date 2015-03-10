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

        return array(
            'bool' => array(
                'must' => $left,
                'must_not' => $right
            )
        );
    }
}

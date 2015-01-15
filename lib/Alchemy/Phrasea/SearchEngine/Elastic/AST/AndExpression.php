<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class AndExpression extends BinaryOperator
{
    protected $operator = 'AND';

    public function buildQuery(QueryContext $context)
    {
        $left  = $this->left->buildQuery($context);
        $right = $this->right->buildQuery($context);

        return array(
            'bool' => array(
                'must' => array($left, $right)
            )
        );
    }
}

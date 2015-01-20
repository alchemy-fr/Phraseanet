<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class OrExpression extends BinaryOperator
{
    protected $operator = 'OR';

    public function buildQuery(QueryContext $context)
    {
        $left  = $this->left->buildQuery($context);
        $right = $this->right->buildQuery($context);

        return array(
            'bool' => array(
                'should' => array($left, $right)
            )
        );
    }
}

<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

class AndExpression extends BinaryOperator
{
    protected $operator = 'AND';

    public function getQuery($fields = ['_all'])
    {
        $left  = $this->left->getQuery($fields);
        $right = $this->right->getQuery($fields);

        return array(
            'bool' => array(
                'must' => array($left, $right)
            )
        );
    }
}

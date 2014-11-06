<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

class ExceptExpression extends BinaryOperator
{
    protected $operator = 'EXCEPT';

    public function getQuery($fields = ['_all'])
    {
        $left  = $this->left->getQuery($fields);
        $right = $this->right->getQuery($fields);

        return array(
            'bool' => array(
                'must' => $left,
                'must_not' => $right
            )
        );
    }
}

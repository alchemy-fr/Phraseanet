<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

class AndExpression extends Node
{
    protected $members = array();

    public function __construct($left, $right)
    {
        $this->members[] = $left;
        $this->members[] = $right;
    }

    public function getMembers()
    {
        return $this->members;
    }

    public function getQuery($fields = ['_all'])
    {
        $rules = array();
        foreach ($this->members as $member) {
            $rules[] = $member->getQuery($fields);
        }

        return array(
            'bool' => array(
                'must' => count($rules) > 1 ? $rules : $rules[0]
            )
        );
    }

    public function __toString()
    {
        return sprintf('(%s)', implode(' AND ', $this->members));
    }
}

<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\QueryException;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Flag as FlagStructure;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Assert\Assertion;

class FlagStatement extends Node
{
    private $name;
    private $set;

    public function __construct($name, $set)
    {
        Assertion::string($name);
        Assertion::boolean($set);
        $this->name = $name;
        $this->set = $set;
    }

    public function buildQuery(QueryContext $context)
    {
        $name = FlagStructure::normalizeName($this->name);
        $flag = $context->getFlag($name);
        if (!$flag) {
            throw new QueryException(sprintf('Flag "%s" does not exist', $this->name));
        }
        return [
            'term' => [
                $flag->getIndexField() => $this->set
            ]
        ];
    }

    public function getTermNodes()
    {
        return array();
    }

    public function __toString()
    {
        return sprintf('<flag:%s %s>', $this->name, $this->set ? 'set' : 'cleared');
    }
}

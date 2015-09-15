<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Assert\Assertion;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;

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
        // TODO Ensure flag exists
        $key = RecordHelper::normalizeFlagKey($this->name);
        $field = sprintf('flags.%s', $key);
        return [
            'term' => [
                $field => $this->set
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

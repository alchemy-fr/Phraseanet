<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class PrefixNode extends Node
{
    protected $prefix;

    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }

    public function buildQuery(QueryContext $context)
    {
        return [
            'multi_match' => [
                'fields'    => $context->getLocalizedFields(),
                'query'     => $this->prefix,
                'type'      => 'phrase_prefix'
            ]
        ];
    }

    public function __toString()
    {
        return sprintf('prefix("%s")', $this->prefix);
    }

    public function getTermNodes()
    {
        // TODO: Implement getTermNodes() method.
    }
}

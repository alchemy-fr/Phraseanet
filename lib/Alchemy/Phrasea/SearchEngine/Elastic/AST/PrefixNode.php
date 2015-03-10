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
        return array(
            'multi_match' => array(
                'fields'    => $context->getLocalizedFields(),
                'query'     => $this->prefix,
                'type'      => 'phrase_prefix'
            )
        );
    }

    public function __toString()
    {
        return sprintf('prefix("%s")', $this->prefix);
    }
}

<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

class PrefixNode extends Node
{
    protected $prefix;

    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }

    public function getQuery($fields = ['_all'])
    {
        return array(
            'multi_match' => array(
                'fields'    => $fields,
                'query'     => $this->prefix,
                'type'      => 'phrase_prefix'
            )
        );
    }

    public function __toString()
    {
        return sprintf('prefix("%s")', $this->prefix);
    }
    
    public function isFullTextOnly()
    {
        return true;
    }
}

<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

class PrefixNode extends Node
{
    protected $prefix;

    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }

    public function getQuery($field = '_all')
    {
        return array(
            'prefix' => array(
                $field => $this->prefix
            )
        );
    }

    public function __toString()
    {
        return sprintf('prefix("%s")', $this->prefix);
    }
}

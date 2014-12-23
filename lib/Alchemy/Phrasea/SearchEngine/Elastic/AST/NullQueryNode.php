<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

class NullQueryNode extends Node
{
    public function getQuery()
    {
        return array('match_all' => array());
    }

    public function getTextNodes()
    {
        return array();
    }

    public function __toString()
    {
        return '<NULL>';
    }

    public function isFullTextOnly()
    {
        return false;
    }
}

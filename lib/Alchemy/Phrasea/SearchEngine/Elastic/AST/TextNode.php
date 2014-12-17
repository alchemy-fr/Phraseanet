<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

class TextNode extends Node
{
    protected $text;

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function getQuery($fields = ['_all'])
    {
        return array(
            'multi_match' => array(
                'fields'    => $fields,
                'query'     => $this->text,
            )
        );
    }

    public function getTextNodes()
    {
        return array($this);
    }

    public function __toString()
    {
        return sprintf('"%s"', $this->text);
    }

    public function isFullTextOnly()
    {
        return true;
    }

    public function getText()
    {
        return $this->text;
    }
}

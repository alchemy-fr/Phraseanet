<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

class TextNode extends Node
{
    protected $text;

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function getQuery($field = '_all')
    {
        return array(
            'match' => array(
                $field => $this->text
            )
        );
    }

    public function __toString()
    {
        return sprintf('"%s"', $this->text);
    }
}

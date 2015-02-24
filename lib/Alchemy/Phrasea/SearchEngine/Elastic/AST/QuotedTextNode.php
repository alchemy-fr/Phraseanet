<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class QuotedTextNode extends Node
{
    private $text;

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function buildQuery(QueryContext $context)
    {
        return array(
            'multi_match' => array(
                'type'      => 'phrase',
                'fields'    => $context->getLocalizedFields(),
                'query'     => $this->text,
                // 'operator'  => 'and'
            )
        );
    }

    public function getTermNodes()
    {
        return array();
    }

    public function __toString()
    {
        return sprintf('<exact_text:"%s">', $this->text);
    }
}

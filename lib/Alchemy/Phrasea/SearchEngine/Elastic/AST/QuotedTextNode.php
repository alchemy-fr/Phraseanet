<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class QuotedTextNode extends TextNode
{
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

    public function __toString()
    {
        return sprintf('<exact_text:"%s">', $this->text);
    }
}

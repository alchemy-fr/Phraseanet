<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

class QuotedTextNode extends TextNode
{
    public function getQuery($field = '_all')
    {
        return array(
            'match' => array(
                $field => array(
                    'query' => $this->text,
                    'operator' => 'and'
                )
            )
        );
    }
}

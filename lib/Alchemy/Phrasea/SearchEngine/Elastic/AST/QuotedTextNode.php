<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

class QuotedTextNode extends TextNode
{
    public function getQuery($fields = ['_all'])
    {
        return array(
            'multi_match' => array(
                'type'      => 'phrase',
                'fields'    => $fields,
                'query'     => $this->text,
                // 'operator'  => 'and'
            )
        );
    }

    public function isFullTextOnly()
    {
        return true;
    }
}

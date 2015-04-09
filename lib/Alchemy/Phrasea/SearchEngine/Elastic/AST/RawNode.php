<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class RawNode extends Node
{
    private $text;

    public static function createFromEscaped($escaped)
    {
        $unescaped = str_replace(
            array('\\\\', '\\"'),
            array('\\', '"'),
            $escaped
        );

        return new self($unescaped);
    }

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function buildQuery(QueryContext $context)
    {
        $fields = $context->getRawFields();
        $query = array();
        if (count($fields) > 1) {
            $query['multi_match']['query'] = $this->text;
            $query['multi_match']['fields'] = $fields;
            $query['multi_match']['analyzer'] = 'keyword';
        } else {
            $field = reset($fields);
            $query['term'][$field] = $this->text;
        }

        return $query;
    }

    public function getTermNodes()
    {
        return array();
    }

    public function __toString()
    {
        return sprintf('<raw:"%s">', $this->text);
    }
}

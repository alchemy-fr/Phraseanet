<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class RecordIdentifierExpression extends Node
{
    private $record_id;

    public function __construct($record_id)
    {
        $this->record_id = $record_id;
    }

    public function buildQuery(QueryContext $context)
    {
        return [
            'term' => [
                'record_id' => $this->record_id
            ]
        ];
    }

    public function getTermNodes()
    {
        return array();
    }

    public function __toString()
    {
        return sprintf('<record_identifier:%s>', $this->record_id);
    }
}

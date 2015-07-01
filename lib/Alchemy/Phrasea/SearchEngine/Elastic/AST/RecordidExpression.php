<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class RecordidExpression extends Node
{
    private $recordid;

    public function __construct($recordid)
    {
        $this->recordid = $recordid;
    }

    public function buildQuery(QueryContext $context)
    {
        $query = array();
        $query['term']['record_id'] = $this->recordid;

        return $query;
    }

    public function getTermNodes()
    {
        return array();
    }

    public function __toString()
    {
        return sprintf('<recordid:%s>', $this->recordid);
    }
}

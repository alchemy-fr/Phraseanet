<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class DatabaseExpression extends Node
{
    private $database;

    public function __construct($database)
    {
        $this->database = $database;
    }

    public function buildQuery(QueryContext $context)
    {
        $query = array();
        $query['term']['databox_name'] = $this->database;

        return $query;
    }

    public function getTermNodes()
    {
        return array();
    }

    public function __toString()
    {
        return sprintf('<database:%s>', $this->database);
    }
}

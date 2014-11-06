<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\AST;
use Hoa\Compiler\Llk\Parser;
use Hoa\Visitor\Element;
use Hoa\Visitor\Visit;


class QueryParser
{
    private $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function parse($string)
    {
        $ast = $this->parser->parse($string);

        $dump = new \Hoa\Compiler\Visitor\Dump();
        echo $dump->visit($ast);

        // Create query from syntax tree
        $visitor = new QueryVisitor();
        $query = $visitor->visit($ast);

        echo "--------------------\n";
        echo (string) $query . "\n";
        echo "--------------------\n";

        return $query;
    }
}


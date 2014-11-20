<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\AST;
use Hoa\Compiler\Llk\Parser;
use Hoa\Compiler\Visitor\Dump as DumpVisitor;
use Hoa\Visitor\Visit;

class QueryParser
{
    private $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Creates a Query object from a string
     */
    public function parse($string)
    {
        return $this->visitString($string, new QueryVisitor());
    }

    public function dump($string)
    {
        return $this->visitString($string, new DumpVisitor());
    }

    private function visitString($string, Visit $visitor)
    {
        $ast = $this->parser->parse($string);

        return $visitor->visit($ast);
    }
}


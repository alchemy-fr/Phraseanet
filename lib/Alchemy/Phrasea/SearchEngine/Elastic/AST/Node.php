<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

abstract class Node
{
    abstract public function getQuery();
}

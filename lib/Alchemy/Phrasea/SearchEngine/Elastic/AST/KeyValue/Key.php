<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue;

interface Key
{
    public function getIndexField();
    public function __toString();
}

<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

class TermNode extends TextNode
{
    public function getQuery()
    {
        throw new \Exception('Corresponding concepts where not linked.');
    }

    public function setConcepts(array $concepts)
    {
        // TODO
    }

    public function __toString()
    {
        return sprintf('<term:%s>', $this->text);
    }
}

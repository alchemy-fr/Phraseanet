<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\TermInterface;

abstract class AbstractTermNode extends Node implements TermInterface
{
    protected $text;
    protected $context;
    private $concepts = [];
    private $pruned_concepts;

    public function __construct($text, Context $context = null)
    {
        $this->text = StringHelper::unescape($text);
        $this->context = $context;
    }

    public function setConcepts(array $concepts)
    {
        $this->pruned_concepts = null;
        $this->concepts = $concepts;
    }

    private function getPrunedConcepts()
    {
        if ($this->pruned_concepts === null) {
            $this->pruned_concepts = Concept::pruneNarrowConcepts($this->concepts);
        }
        return $this->pruned_concepts;
    }

    protected function buildConceptQueries(array $fields)
    {
        $concepts = $this->getPrunedConcepts();
        if (!$concepts) {
            return [];
        }

        $index_fields = [];
        foreach ($fields as $field) {
            $index_fields[] = $field->getConceptPathIndexField();
        }
        if (!$index_fields) {
            return [];
        }

        $queries = [];
        foreach ($concepts as $concept) {
            $queries[] = [
                'multi_match' => [
                    'fields'   => $index_fields,
                    'query'    => $concept->getPath()
                ]
            ];
        }
        return $queries;
    }

    public function getValue()
    {
        return $this->text;
    }

    public function hasContext()
    {
        return $this->context !== null;
    }

    public function getContext()
    {
        return $this->context->getValue();
    }

    public function getTermNodes()
    {
        return [$this];
    }
}

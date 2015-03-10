<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\TermInterface;

abstract class AbstractTermNode extends Node implements TermInterface
{
    protected $text;
    protected $context;
    private $concepts = array();

    public function __construct($text, Context $context = null)
    {
        $this->text = $text;
        $this->context = $context;
    }

    public function setConcepts(array $concepts)
    {
        $this->concepts = $concepts;
    }

    protected function buildConceptQueries(QueryContext $context)
    {
        $queries = array();
        $concepts = Concept::pruneNarrowConcepts($this->concepts);
        $fields = $context->getFields();
        if (empty($fields)) {
            $fields = array('*');
        }
        $prefixedFields = array();
        foreach ($fields as $field) {
            $prefixedFields[] = 'concept_path.' . $field;
        }
        foreach ($concepts as $concept) {
            $queries[]['multi_match'] = array(
                'fields' => $prefixedFields,
                'query' => $concept->getPath()
            );
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
        return array($this);
    }
}

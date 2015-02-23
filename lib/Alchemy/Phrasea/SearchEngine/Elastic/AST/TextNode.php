<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\TermInterface;

class TextNode extends Node implements TermInterface
{
    protected $text;
    protected $concepts = array();

    public function __construct($text)
    {
        $this->text = $text;
    }

    public function setConcepts(array $concepts)
    {
        $this->concepts = $concepts;
    }

    public function buildQuery(QueryContext $context)
    {
        $query = array(
            'multi_match' => array(
                'fields'    => $context->getLocalizedFields(),
                'query'     => $this->text,
            )
        );

        if ($conceptQueries = $this->buildConceptQueries()) {
            $textQuery = $query;
            $query = array();
            $query['bool']['should'] = $conceptQueries;
            $query['bool']['should'][] = $textQuery;
        }

        return $query;
    }

    protected function buildConceptQueries()
    {
        $queries = array();
        foreach (Concept::pruneNarrowConcepts($this->concepts) as $concept) {
            $queries[]['term']['concept_paths'] = $concept->getPath();
        }

        return $queries;
    }

    public function getTextNodes()
    {
        return array($this);
    }

    public function __toString()
    {
        return sprintf('<text:"%s">', $this->text);
    }


    // Implementation of TermInterface

    public function getValue()
    {
        return $this->text;
    }

    public function hasContext()
    {
        return false;
    }

    public function getContext()
    {
        // TODO Insert context during parsing
        return null;
    }
}

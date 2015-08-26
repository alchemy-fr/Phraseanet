<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;
use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\TermInterface;

abstract class AbstractTermNode extends Node implements TermInterface
{
    protected $text;
    protected $context;
    private $concepts = [];

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
        $concept_query = $this->buildConceptQuery($context);
        if ($concept_query === null) {
            return [];
        }

        // Extract all should clauses
        if (
            isset($concept_query['bool']) &&
            isset($concept_query['bool']['should']) &&
            count($concept_query) === 1 /* no options or must(_not) clauses */
        ) {
            return isset($concept_query['bool']['should'][0]) ?
                $concept_query['bool']['should'] :
                [$concept_query['bool']['should']];
        }

        // Fallback to returning full query
        return [$concept_query];
    }

    protected function buildConceptQuery(QueryContext $context)
    {
        $concepts = Concept::pruneNarrowConcepts($this->concepts);
        if (!$concepts) {
            return null;
        }

        $query_builder = function (array $fields) use ($concepts) {
            $index_fields = [];
            foreach ($fields as $field) {
                $index_fields[] = $field->getConceptPathIndexField();
            }
            if (!$index_fields) {
                return null;
            }
            $query = null;
            foreach ($concepts as $concept) {
                $concept_query = [
                    'multi_match' => [
                        'fields'   => $index_fields,
                        'query'    => $concept->getPath()
                    ]
                ];
                $query = QueryHelper::applyBooleanClause($query, 'should', $concept_query);
            }
            return $query;
        };

        $query = $query_builder($context->getUnrestrictedFields());
        $private_fields = $context->getPrivateFields();
        foreach (QueryHelper::wrapPrivateFieldConceptQueries($private_fields, $query_builder) as $private_field_query) {
            $query = QueryHelper::applyBooleanClause($query, 'should', $private_field_query);
        }

        return $query;
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

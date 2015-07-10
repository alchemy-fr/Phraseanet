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
        $concepts = Concept::pruneNarrowConcepts($this->concepts);
        if (!$concepts) {
            return [];
        }

        $queries_builder = function (array $index_fields) use ($concepts) {
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
        };

        $fields = $context->getUnrestrictedFields();
        $index_fields = Field::toConceptPathIndexFieldArray($fields);

        $queries = $queries_builder($index_fields);
        foreach (QueryHelper::buildPrivateFieldConceptQueries($context, $queries_builder) as $queries[]);

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

<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST;

use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\TermInterface;

abstract class AbstractTermNode extends Node implements TermInterface
{
    protected $text;
    protected $context;
    private $concepts = [];
    /**
     * pruned concepts is a reduced list of concepts, keeping only high-level ones
     * by removing concepts-included-in-concept,
     * e.g.
     * /1/animal/mamal
     * /1/animal/mamal/dog      -- removed because included
     * /2/subject/animal
     */
    private $pruned_concepts;

    public function __construct($text, Context $context = null)
    {
        $this->text = $text;
        $this->context = $context;
    }

    public function setConcepts(array $concepts)
    {
        $this->pruned_concepts = null;
        $this->concepts = $concepts;
    }

    /**
     * @return Concept[]
     */
    private function getPrunedConcepts()
    {
        if ($this->pruned_concepts === null) {
            $this->pruned_concepts = Concept::pruneNarrowConcepts($this->concepts);
        }
        return $this->pruned_concepts;
    }

    /**
     * @param Field[] $fields
     * @return array
     */
    protected function buildConceptQueries(array $fields)
    {
        $concepts = $this->getPrunedConcepts();
        if (!$concepts) {
            return [];
        }

        $index_fields = [];
        foreach ($fields as $field) {
            // $db = $field->get_databox_id();
            foreach ($field->getDependantDataboxes() as $db) {
                if(!array_key_exists($db, $index_fields)) {
                    $index_fields[$db] = [];
                }
                $index_fields[$db][] = $field->getConceptPathIndexField();
            }
        }

        $queries = [];
        foreach ($concepts as $concept) {
            $db = $concept->getDataboxId();
            if(array_key_exists($db, $index_fields)) {
                $queries[] = [
                    'multi_match' => [
                        'fields' => $index_fields[$db],
                        'query'  => $concept->getPath()
                    ]
                ];
            }
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

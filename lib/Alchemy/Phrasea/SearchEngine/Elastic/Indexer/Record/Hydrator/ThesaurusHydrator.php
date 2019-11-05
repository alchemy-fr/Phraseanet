<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator;

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\Exception;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\CandidateTerms;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Filter;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Term;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;

class ThesaurusHydrator implements HydratorInterface
{
    private $structure;
    private $thesaurus;
    private $candidate_terms;

    public function __construct(Structure $structure, Thesaurus $thesaurus, CandidateTerms $candidate_terms)
    {
        $this->structure = $structure;
        $this->thesaurus = $thesaurus;
        $this->candidate_terms = $candidate_terms;
    }

    public function hydrateRecords(array &$records)
    {
        // Fields with concept inference enabled
        /** @var Field[] $structure */
        $structure = $this->structure->getThesaurusEnabledFields();
        $fields = [];
        $index_fields = [];
        foreach ($structure as $name => $field) {
            $fields[$name] = $field; // ->getThesaurusRoots();
            $index_fields[$name] = $field->getIndexField();
        }
        // Hydrate records with concepts
        foreach ($records as &$record) {
            $this->hydrate($record, $fields, $index_fields);
        }
    }

    /**
     * @param array $record
     * @param  Field[] $fields
     * @param array $index_fields
     * @throws Exception
     */
    private function hydrate(array &$record, $fields, array $index_fields)
    {
        if (!isset($record['databox_id'])) {
            throw new Exception('Expected a record with the "databox_id" key set.');
        }

        $values = array();
        $terms = array();
        $filters = array();
        $field_names = array();
        foreach ($fields as $name => $field) {
            $root_concepts = $field->getThesaurusRoots();
            // Loop through all values to prepare bulk query
            $field_values = \igorw\get_in($record, explode('.', $index_fields[$name]));
            if ($field_values !== null) {
                // Concepts are databox's specific, but when no root concepts are
                // given we need to make sure we only match in the right databox.
                $filter = $root_concepts
                    ? Filter::childOfConcepts($record['databox_id'], $root_concepts)
                    : Filter::byDatabox($record['databox_id']);
                foreach ($field_values as $value) {
                    $values[] = $value;
                    $terms[] = Term::parse($value);
                    $filters[] = $filter;
                    $field_names[] = $name;
                }
            }
        }
        if(empty($terms)) {
            return;
        }
        $bulk = $this->thesaurus->findConceptsBulk($terms, null, $filters, true);

        foreach ($bulk as $offset => $item_concepts) {
            $name = $field_names[$offset];
            if ($item_concepts && is_array($item_concepts) && count($item_concepts)>0) {
                foreach ($item_concepts as $concept) {
                    $record['concept_path'][$name][] = $concept->getPath();
                }
            }
            else {
                $field = $fields[$name];
                if($field->get_generate_cterms()) {
                    $this->candidate_terms->insert($field_names[$offset], $values[$offset]);
                }
            }
        }
    }
}

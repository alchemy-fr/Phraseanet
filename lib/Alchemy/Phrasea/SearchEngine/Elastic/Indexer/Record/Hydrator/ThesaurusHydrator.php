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
        $structure = $this->structure->getThesaurusEnabledFields();
        $fields = array();
        foreach ($structure as $name => $field) {
            $fields[$name] = $field->getThesaurusRoots();
        }
        // Hydrate records with concepts
        foreach ($records as &$record) {
            $this->hydrate($record, $fields);
        }
    }

    private function hydrate(array &$record, array $fields)
    {
        if (!isset($record['databox_id'])) {
            throw new Exception('Expected a record with the "databox_id" key set.');
        }

        $values = array();
        $terms = array();
        $filters = array();
        $field_names = array();
        foreach ($fields as $name => $root_concepts) {
            // Concepts are databox's specific, but when no root concepts are
            // given we need to make sure we only match in the right databox.
            $filter = $root_concepts
                ? Filter::childOfConcepts($root_concepts)
                : Filter::byDatabox($record['databox_id']);
            // Loop through all values to prepare bulk query
            if (isset($record['caption'][$name])) {
                foreach ($record['caption'][$name] as $value) {
                    $values[] = $value;
                    $terms[] = Term::parse($value);
                    $filters[] = $filter;
                    $field_names[] = $name;
                }
            }
        }

        $bulk = $this->thesaurus->findConceptsBulk($terms, null, $filters, true);

        foreach ($bulk as $offset => $item_concepts) {
            if ($item_concepts && is_array($item_concepts) && count($item_concepts)>0) {
                $name = $field_names[$offset];
                foreach ($item_concepts as $concept) {
                    $record['concept_path'][$name][] = $concept->getPath();
                }
            } else {
                $this->candidate_terms->insert($field_names[$offset], $values[$offset]);
            }
        }
    }
}

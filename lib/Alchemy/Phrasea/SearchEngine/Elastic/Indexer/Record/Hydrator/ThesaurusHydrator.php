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
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Concept;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Filter;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Term;

class ThesaurusHydrator implements HydratorInterface
{
    private $thesaurus;
    private $helper;

    public function __construct(Thesaurus $thesaurus, RecordHelper $helper)
    {
        $this->thesaurus = $thesaurus;
        $this->helper = $helper;
    }

    public function hydrateRecords(array &$records)
    {
        // Fields with concept inference enabled
        $structure = $this->helper->getFieldsStructure();
        $fields = array();
        foreach ($structure as $field => $options) {
            if ($options['thesaurus_concept_inference']) {
                $fields[$field] = $options['thesaurus_prefix'];
            }
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
        $filter = Filter::byDatabox($record['databox_id']);

        $candidate_terms = array();
        foreach ($fields as $field => $prefix) {
            if (!isset($record['caption'][$field])) {
                continue;
            }

            // TODO Build prefix filter

            $concepts = array();
            foreach ($record['caption'][$field] as $value) {
                $term = Term::parse($value);
                $item_concepts = $this->thesaurus->findConcepts($term, null, $filter, true);
                if ($item_concepts) {
                    foreach ($item_concepts as $concepts[]);
                } else {
                    $candidate_terms[] = $value;
                }
            }
            if ($concepts) {
                $record['concept_path'][$field] = Concept::toPathArray($concepts);
            }
        }

        // TODO store candidate terms
    }
}

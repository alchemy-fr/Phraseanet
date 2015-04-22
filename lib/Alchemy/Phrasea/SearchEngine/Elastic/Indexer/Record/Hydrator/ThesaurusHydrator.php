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

class ThesaurusHydrator implements HydratorInterface
{
    private $thesaurus;
    private $helper;

    public function __construct(Thesaurus $thesaurus, CandidateTerms $candidateTerms, RecordHelper $helper)
    {
        $this->thesaurus = $thesaurus;
        $this->candidateTerms = $candidateTerms;
        $this->helper = $helper;
    }

    public function hydrateRecords(array &$records)
    {
        // Fields with concept inference enabled
        $structure = $this->helper->getFieldsStructure();
        $fields = array();
        foreach ($structure as $name => $options) {
            if ($options['thesaurus_concept_inference']) {
                $fields[$name] = $options['thesaurus_prefixes'];
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

        $terms = array();
        $bulkFieldMap = array();
        foreach ($fields as $name => $prefixes) {
            if (isset($record['caption'][$name])) {
                // Loop through all values to prepare bulk query
                foreach ($record['caption'][$name] as $value) {
                    $terms[] = Term::parse($value);
                    $bulkFieldMap[] = $name;
                }
            }
        }

        // TODO Build prefix filter
        $filter = Filter::byDatabox($record['databox_id']);
        $bulk = $this->thesaurus->findConceptsBulk($terms, null, $filter, true);

        foreach ($bulk as $offset => $item_concepts) {
            if ($item_concepts) {
                $name = $bulkFieldMap[$offset];
                foreach ($item_concepts as $concept) {
                    $record['concept_path'][$name][] = $concept->getPath();
                }
            } else {
                $this->candidateTerms->insert($name, $value);
            }
        }
    }
}

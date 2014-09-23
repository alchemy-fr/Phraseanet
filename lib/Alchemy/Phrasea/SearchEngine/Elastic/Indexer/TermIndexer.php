<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer;

use Alchemy\Phrasea\SearchEngine\Elastic\BulkOperation;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Navigator;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\TermVisitor;
use databox;
use DOMDocument;

class TermIndexer
{
    const TYPE_NAME = 'term';

    /**
     * @var \appbox
     */
    private $appbox;

    private $navigator;
    private $locales;

    public function __construct(\appbox $appbox, array $locales)
    {
        $this->appbox = $appbox;
        $this->navigator = new Navigator();
        $this->locales = $locales;
    }

    public function populateIndex(BulkOperation $bulk)
    {
        foreach ($this->appbox->get_databoxes() as $databox) {
            /** @var databox $databox */
            $databoxId              = $databox->get_sbas_id();
            $document               = self::thesaurusFromDatabox($databox);
            $dedicatedFieldTerms    = $this->getDedicatedFieldTerms($databox, $document);

            $visitor = new TermVisitor(function ($term) use ($bulk, $databoxId, $dedicatedFieldTerms) {
                //printf("- %s (%s)\n", $term['path'], $term['value']);
                // Term structure
                $id = $term['id'];
                unset($term['id']);
                $term['databox_id'] = $databoxId;

                // @todo move to the TermVisitor? dunno.
                $term['fields'] = null;
                foreach ($dedicatedFieldTerms as $partialId => $fields) {
                    if (strpos($id, $partialId) === 0) {
                        foreach ($fields as $field) {
                            $term['fields'][] = $field;
                        }
                    }
                }

                // Index request
                $params = array();
                $params['id'] = $id;
                $params['type'] = self::TYPE_NAME;
                $params['body'] = $term;
                $bulk->index($params);
            });

            $this->navigator->walk($document, $visitor);
        }
    }

    private static function thesaurusFromDatabox(databox $databox)
    {
        $dom = $databox->get_dom_thesaurus();
        if (!$dom) {
            $dom = new DOMDocument('1.0', 'UTF-8');
        }

        return $dom;
    }

    private function getDedicatedFieldTerms(databox $databox, DOMDocument $document)
    {
        $xpath = new DOMXpath($document);
        $dedicatedFieldTerms = [];

        foreach ($databox->get_meta_structure() as $f) {
            if ($f->get_tbranch()) {
                $elements = $xpath->query($f->get_tbranch());

                if ($elements) {
                    foreach ($elements as $element) {
                        $dedicatedFieldTerms[$element->getAttribute('id')][] = $f->get_name();
                    }
                }
            }
        }

        return $dedicatedFieldTerms;
    }

    public function getMapping()
    {
        $mapping = new Mapping();
        $mapping
            ->add('raw_value', 'string')->notAnalyzed()
            ->add('value', 'string')->addAnalyzedVersion($this->locales)
            ->add('context', 'string')
            ->add('path', 'string')->notAnalyzed()
            ->add('lang', 'string')->notAnalyzed()
            ->add('databox_id', 'integer')
            ->add('fields', 'string')->notAnalyzed()
        ;

        return $mapping->export();
    }
}

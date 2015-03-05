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

use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\BulkOperation;
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

    public function populateIndex(BulkOperation $bulk, array $databoxes)
    {
        foreach ($databoxes as $databox) {
            /** @var databox $databox */
            $databoxId = $databox->get_sbas_id();

            $visitor = new TermVisitor(function ($term) use ($bulk, $databoxId) {
                // Path and id are prefixed with a databox identifier to not
                // collide with other databoxes terms

                // Term structure
                $id = sprintf('%s_%s', $databoxId, $term['id']);
                unset($term['id']);
                $term['path'] = sprintf('/%s%s', $databoxId, $term['path']);
                $term['databox_id'] = $databoxId;

                // Index request
                $params = array();
                $params['id'] = $id;
                $params['type'] = self::TYPE_NAME;
                $params['body'] = $term;

                $bulk->index($params);
            });

            $document  = self::thesaurusFromDatabox($databox);
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

    public function getMapping()
    {
        $mapping = new Mapping();
        $mapping
            ->add('raw_value', 'string')->notAnalyzed()
            ->add('value', 'string')
                ->analyzer('general_light')
                ->addLocalizedSubfields($this->locales)
            ->add('context', 'string')
                ->analyzer('general_light')
                ->addLocalizedSubfields($this->locales)
            ->add('path', 'string')->notAnalyzed()
            ->add('lang', 'string')->notAnalyzed()
            ->add('databox_id', 'integer')
        ;

        return $mapping->export();
    }
}

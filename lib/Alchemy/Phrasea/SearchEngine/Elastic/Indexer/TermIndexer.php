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
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticSearchEngine;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Navigator;
use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\TermVisitor;
use Closure;
use Elasticsearch\Client;
use databox;
use DOMDocument;

class TermIndexer
{
    const TYPE_NAME = 'term';

    private $bulkOperationFactory;
    /**
     * @var \appbox
     */
    private $appbox;

    private $navigator;

    public function __construct(\appbox $appbox)
    {
        $this->appbox = $appbox;
        $this->navigator = new Navigator();
    }

    public function populateIndex(BulkOperation $bulk)
    {
        foreach ($this->appbox->get_databoxes() as $databox) {
            $databoxId = $databox->get_sbas_id();
            $document = self::thesaurusFromDatabox($databox);
            $visitor = new TermVisitor(function ($term) use ($bulk, $databoxId) {
                // printf("- %s (%s)\n", $term['path'], $term['value']);
                // Term structure
                $id = $term['id'];
                unset($term['id']);
                $term['databox_id'] = $databoxId;
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

    public function getMapping()
    {
        $mapping = new Mapping();
        $mapping
            ->add('raw_value', 'string')->notAnalyzed()
            ->add('value', 'string')
            ->add('context', 'string')
            ->add('path', 'string')->notAnalyzed()
            ->add('lang', 'string')->notAnalyzed()
            ->add('databox_id', 'integer')
        ;

        return $mapping->export();
    }
}

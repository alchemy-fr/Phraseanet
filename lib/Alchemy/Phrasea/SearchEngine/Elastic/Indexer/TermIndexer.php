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

    public function __construct(\appbox $appbox)
    {
        $this->appbox = $appbox;
    }

    public function populateIndex(BulkOperation $bulk)
    {
        // TODO Create object to query thesaurus for term paths/synonyms

        $navigator = new Navigator();

        foreach ($this->appbox->get_databoxes() as $databox) {
            $document = self::thesaurusFromDatabox($databox);
            $visitor = new TermVisitor(function ($term) use ($bulk) {
                printf("- %s (%s)\n", $term['path'], $term['value']);
            });
            $navigator->walk($document, $visitor);

            while ($record = false) {
                $params = array();
                $params['id'] = $record['id'];
                $params['body'] = $record;
                $bulk->index($params);
            }
        }

        $bulk->flush();
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
            ->add('value', 'string')
            ->add('context', 'string')
            ->add('path', 'string')
            ->add('lang', 'string')->notAnalyzed()
            ->add('databox_id', 'integer')
        ;

        return $mapping->export();
    }
}

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
use Elasticsearch\Client;
use databox;
use DOMDocument;

class TermIndexer
{
    const TYPE_NAME = 'term';

    private $client;
    private $options;
    /**
     * @var \appbox
     */
    private $appbox;

    public function __construct(Client $client, array $options, \appbox $appbox)
    {
        $this->client = $client;
        $this->options = $options;
        //$this->document = self::thesaurusFromDatabox($databox);
        $this->appbox = $appbox;
    }

    public function populateIndex()
    {
        // Prepare the bulk operation
        $bulk = new BulkOperation($this->client);
        $bulk->setDefaultIndex($this->options['index']);
        $bulk->setDefaultType(self::TYPE_NAME);
        $bulk->setAutoFlushLimit(1000);

        // Helper to fetch record related data
        //$recordHelper = new RecordHelper($this->appbox);

        foreach ($this->appbox->get_databoxes() as $databox) {
            // TODO Create object to query thesaurus for term paths/synonyms
            // TODO Extract record indexing logic in a RecordIndexer class
            //$fetcher = new RecordFetcher($databox, $recordHelper);
            //$fetcher->setBatchSize(200);
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

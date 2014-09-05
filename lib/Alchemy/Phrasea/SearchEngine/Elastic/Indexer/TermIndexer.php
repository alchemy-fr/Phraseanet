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

use Elasticsearch\Client;
use databox;
use DOMDocument;

class TermIndexer
{
    private $client;
    private $options;
    private $document;

    public function __construct(Client $client, array $options, databox $databox)
    {
        $this->client = $client;
        $this->options = $options;
        $this->document = self::thesaurusFromDatabox($databox);
    }

    public function populateIndex()
    {
        // TODO Extract terms from thesaurus document and index them in ES
    }

    private static function thesaurusFromDatabox(databox $databox)
    {
        $dom = $databox->get_dom_thesaurus();
        if (!$dom) {
            $dom = new DOMDocument('1.0', 'UTF-8');
        }

        return $dom;
    }
}

<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus;

use databox;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Elasticsearch\Client;

class Helper
{
    public function findNodesByXPath($document, $xpath)
    {
        $tbranch = "/thesaurus/te[@id='T26'] | /thesaurus/te[@id='T24']";
        $xpath = new \DOMXPath($document);
        $nodeList = $xpath->query($tbranch);
        $conceptIds = [];
        foreach ($nodeList as $node) {
            if ($node->hasAttribute('id')) {
                $conceptIds[] = $node->getAttribute('id');
            }
        }

    }

    public static function thesaurusFromDatabox(databox $databox)
    {
        return self::document($databox->get_dom_thesaurus());
    }

    public static function candidatesFromDatabox(databox $databox)
    {
        $document = $databox->get_dom_cterms();
        if (!$document) {
            $document = new DOMDocument('1.0', 'UTF-8');
            $document->xmlStandalone = true;
            $document->formatOutput = true;
            $element = $document->createElement('cterms');
            $element->setAttribute('creation_date', date('YmdHis'));
            $element->setAttribute('next_id', 0);
            $element->setAttribute('version', '2.0.5');
            $document->appendChild($element);
        }

        return $document;
    }

    private static function document($document)
    {
        if (!$document) {
            return new DOMDocument('1.0', 'UTF-8');
        }

        return $document;
    }
}

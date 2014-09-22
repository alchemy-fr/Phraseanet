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
}

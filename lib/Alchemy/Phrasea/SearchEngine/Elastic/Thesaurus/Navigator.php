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

use Alchemy\Phrasea\SearchEngine\Elastic\BulkOperation;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticSearchEngine;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Closure;
use Elasticsearch\Client;
use databox;

use DOMDocument;
use DOMElement;
use DOMNode;

class Navigator
{
    const THESAURUS_TAG_NAME = 'thesaurus';
    const CONCEPT_TAG_NAME = 'te';
    const TERM_TAG_NAME = 'sy';

    public function walk(DOMNode $node, VisitorInterface $visitor)
    {
        if ($this->isConcept($node)) {
            $visitor->visitConcept($node);
            foreach ($node->childNodes as $child) {
                $this->walk($child, $visitor);
            }
            $visitor->leaveConcept($node);
        } elseif ($this->isTerm($node)) {
            $visitor->visitTerm($node);
        } else {
            foreach ($node->childNodes as $child) {
                $this->walk($child, $visitor);
            }
        }
    }

    private function isConcept(DOMNode $node)
    {
        return $node instanceof DOMElement && $node->tagName === self::CONCEPT_TAG_NAME;
    }

    private function isTerm(DOMNode $node)
    {
        return $node instanceof DOMElement && $node->tagName === self::TERM_TAG_NAME;
    }
}

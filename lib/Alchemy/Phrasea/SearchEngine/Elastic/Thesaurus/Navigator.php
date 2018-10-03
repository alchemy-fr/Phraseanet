<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus;

use DOMElement;
use DOMNode;

class Navigator
{
    const THESAURUS_TAG_NAME = 'thesaurus';
    const CONCEPT_TAG_NAME = 'te';
    const TERM_TAG_NAME = 'sy';

    public function walk(DOMNode $node, VisitorInterface $visitor)
    {
        if (self::isConcept($node)) {
            $visitor->visitConcept($node);
            foreach ($node->childNodes as $child) {
                $this->walk($child, $visitor);
            }
            $visitor->leaveConcept($node);
        } elseif (self::isTerm($node)) {
            $visitor->visitTerm($node);
        } elseif ($node->childNodes !== null) {
            // Sometimes childNodes is NULL (i.e. DOMText)
            foreach ($node->childNodes as $child) {
                $this->walk($child, $visitor);
            }
        }
    }

    public static function isConcept(DOMNode $node)
    {
        return $node instanceof DOMElement && $node->tagName === self::CONCEPT_TAG_NAME;
    }

    public static function isTerm(DOMNode $node)
    {
        return $node instanceof DOMElement && $node->tagName === self::TERM_TAG_NAME;
    }
}

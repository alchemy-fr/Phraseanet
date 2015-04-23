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

use Alchemy\Phrasea\SearchEngine\Elastic\Thesaurus\Helper;
use Closure;
use Elasticsearch\Client;
use DOMElement;

class TermVisitor implements VisitorInterface
{
    const TERM_ID_ATTR = 'id';
    const TERM_LANG_ATTR = 'lng';
    const TERM_VALUE_ATTR = 'v';

    private $path = [];
    private $termCallback;

    public function __construct(Closure $termCallback)
    {
        $this->termCallback = $termCallback;
    }

    public function visitConcept(DOMElement $element)
    {
        array_push($this->path, Helper::conceptPathSegment($element));
    }

    public function visitTerm(DOMElement $element)
    {
        $raw_value = $element->getAttribute(self::TERM_VALUE_ATTR);
        $object = Term::parse($raw_value);
        $term = [
            'raw_value' => $raw_value,
            'value'     => $object->getValue(),
            'context'   => $object->getContext(),
            'path'      => $this->getCurrentPathAsString(),
            'lang'      => $element->getAttribute(self::TERM_LANG_ATTR),
            'id'        => $element->getAttribute(self::TERM_ID_ATTR)
        ];

        call_user_func($this->termCallback, $term);
    }

    public function leaveConcept(DOMElement $element)
    {
        array_pop($this->path);
    }

    private function getCurrentPathAsString()
    {
        return sprintf('/%s', implode('/', $this->path));
    }
}

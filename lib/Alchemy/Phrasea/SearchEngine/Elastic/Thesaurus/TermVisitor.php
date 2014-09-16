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
use Alchemy\Phrasea\SearchEngine\Elastic\StringUtils;
use Alchemy\Phrasea\SearchEngine\Elastic\Exception\ThesaurusException;
use Closure;
use Elasticsearch\Client;
use DOMDocument;
use DOMNodeList;
use DOMElement;
use DOMNode;

class TermVisitor implements VisitorInterface
{
    const TERM_TAG_NAME = 'sy';
    const TERM_ID_ATTR = 'id';
    const TERM_LANG_ATTR = 'lng';
    const TERM_VALUE_ATTR = 'v';
    // So, this is a huuuge regex to match a group of words eventually followed
    // by another group of words in parenthesis. It also takes care of trimming
    // spaces.
    const TERM_REGEX = '/^\s*(\w[^\(\)]*\w|\w)\s*(?:\(\s*([^\(\)]*[^\s\(\)])\s*\))?/u';
    //                       [_____term______]       (   [_____context_____]    )

    const PATH_LANG = 'en';

    private $path = [];
    private $termCallback;

    public function __construct(Closure $termCallback)
    {
        $this->termCallback = $termCallback;
    }

    public function visitConcept(DOMElement $element)
    {
        array_push($this->path, $this->getConceptPathSegment($element));
    }

    public function visitTerm(DOMElement $element)
    {
        $value = $this->getTermValue($element);

        $term = $this->parseTermValue($value);
        $term += [
            'path' => $this->getCurrentPathAsString(),
            'lang' => $this->getTermAttribute($element, self::TERM_LANG_ATTR),
            'id'   => $this->getTermAttribute($element, self::TERM_ID_ATTR)
        ];

        call_user_func($this->termCallback, $term);
    }

    public function leaveConcept(DOMElement $element)
    {
        array_pop($this->path);
    }

    private function parseTermValue($value)
    {
        preg_match(self::TERM_REGEX, $value, $matches);

        return [
            'raw_value' => $value,
            'value'     => isset($matches[1]) ? $matches[1] : null,
            'context'   => isset($matches[2]) ? $matches[2] : null
        ];
    }

    private function getCurrentPathAsString()
    {
        return sprintf('/%s', implode('/', $this->path));
    }

    private function getConceptPathSegment(DOMElement $element)
    {
        // Path segment is named according to the first english term, and
        // default to the first term.
        $terms = $this->filter($element->childNodes, array($this, 'isTerm'));
        $term = $this->find($terms, array($this, 'isPathLang'));
        if (!$term) {
            if (isset($terms[0])) {
                $term = $terms[0];
            } else {
                throw new ThesaurusException(sprintf('No term linked to concept at path "%s".', $element->getNodePath()));
            }
        }

        return StringUtils::slugify($this->getTermValue($term));
    }

    private function isTerm(DOMNode $node)
    {
        return $node instanceof DOMElement && $node->tagName === self::TERM_TAG_NAME;
    }

    private function isPathLang(DOMElement $element)
    {
        return $element->getAttribute(self::TERM_LANG_ATTR) === self::PATH_LANG;
    }

    private function getTermValue(DOMElement $term)
    {
        return $this->getTermAttribute($term, self::TERM_VALUE_ATTR);
    }

    private function getTermAttribute(DOMElement $term, $attribute)
    {
        if ($term->hasAttribute($attribute)) {
            return $term->getAttribute($attribute);
        }
    }

    // DOM Helpers

    private function filter(DOMNodeList $list, Callable $callback)
    {
        $filtered = [];
        foreach ($list as $node) {
            if (call_user_func($callback, $node)) {
                $filtered[] = $node;
            }
        }

        return $filtered;
    }

    private function find(array $list, Callable $callback)
    {
        foreach ($list as $node) {
            if (call_user_func($callback, $node)) {
                return $node;
            }
        }
    }
}

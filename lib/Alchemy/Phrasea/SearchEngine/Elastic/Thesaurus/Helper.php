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

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\ThesaurusException;
use Alchemy\Phrasea\SearchEngine\Elastic\StringUtils;
use databox;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;

class Helper
{
    const TERM_LANG_ATTR = 'lng';
    const TERM_VALUE_ATTR = 'v';
    const PATH_LANG = 'en';

    public static function findConceptsByXPath(databox $databox, $expression)
    {
        $document = self::thesaurusFromDatabox($databox);
        $xpath = new DOMXPath($document);
        $nodes = $xpath->query($expression);
        $concepts = [];

        /** @var DOMElement $node */
        foreach ($nodes as $node) {
            $me_and_parents = array_merge([$node], self::getElementAncestors($node));

            $path_segments = [];

            foreach ($me_and_parents as $me_or_parent) {
                if (!Navigator::isConcept($me_or_parent)) {
                    // Silently skips invalid targeted nodes
                    break;
                }

                $path_segments[] = self::conceptPathSegment($me_or_parent);
            }

            // Concept paths are have databox identifier at root level
            $concepts[] = new Concept(
                $databox->get_sbas_id(),
                sprintf(
                    '/%d/%s',
                    $databox->get_sbas_id(),
                    implode('/', array_reverse($path_segments))
                ));
        }

        return $concepts;
    }

    private static function getElementAncestors(DOMElement $element)
    {
        $parents = [];

        while (null !== $element = $element->parentNode) {
            $parents[] = $element;
        }

        return $parents;
    }

    /**
     * @param databox $databox
     * @return DOMDocument
     */
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
            $element->setAttribute('nextid', 0);
            $element->setAttribute('version', '2.0.5');
            $document->appendChild($element);
        }

        return $document;
    }

    /**
     * @param $document
     * @return DOMDocument
     */
    private static function document($document)
    {
        if (!$document) {
            return new DOMDocument('1.0', 'UTF-8');
        }

        return $document;
    }

    public static function conceptPathSegment(DOMElement $element)
    {
        // Path segment is named according to the first english term, and
        // default to the first term.
        $terms = self::filter($element->childNodes, array(Navigator::class, 'isTerm'));
        $term = self::find($terms, array('self', 'isPathLang'));
        if (!$term) {
            if (isset($terms[0])) {
                $term = $terms[0];
            } else {
                throw new ThesaurusException(sprintf('No term linked to concept at path "%s".', $element->getNodePath()));
            }
        }

        return StringUtils::slugify($term->getAttribute(self::TERM_VALUE_ATTR));
    }

    private static function isPathLang(DOMElement $element)
    {
        return $element->getAttribute(self::TERM_LANG_ATTR) === self::PATH_LANG;
    }

    // DOM Helpers

    private static function filter(DOMNodeList $list, callable $callback)
    {
        $filtered = [];
        foreach ($list as $node) {
            if (call_user_func($callback, $node)) {
                $filtered[] = $node;
            }
        }

        return $filtered;
    }

    private static function find(array $list, callable $callback)
    {
        foreach ($list as $node) {
            if (call_user_func($callback, $node)) {
                return $node;
            }
        }
    }
}

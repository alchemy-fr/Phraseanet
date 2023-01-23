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

use Alchemy\Phrasea\SearchEngine\Elastic\StringUtils;
use databox;
use DOMDocument;
use DOMElement;
use DOMNode;
use Transliterator;

class CandidateTerms
{
    private $databox;
    private $new_candidates = array();
    private $visitor;
    private $document;

    public function __construct(databox $databox)
    {
        $this->databox = $databox;
    }

    public function insert($field, $value)
    {
        $value = StringUtils::substituteCtrlCharacters($value, '');
        $this->ensureVisitorSetup();
        if (!$this->visitor->hasTerm($field, $value)) {
            $this->new_candidates[$value] = $field;
        }
    }

    private function ensureVisitorSetup()
    {
        if (!$this->visitor) {
            $navigator = new Navigator();
            $this->visitor = new CandidateTermVisitor();
            $this->ensureDocumentLoaded();
            $navigator->walk($this->document, $this->visitor);
        }
    }

    public function save()
    {
        $this->ensureDocumentLoaded();
        foreach ($this->new_candidates as $raw_value => $field) {
            $term = Term::parse($raw_value);
            $norm_value = StringUtils::asciiLowerFold($term->getValue());
            $norm_context = StringUtils::asciiLowerFold($term->getContext());
            $element = $this->createElement($raw_value, $norm_value, $norm_context);
            $container = $this->findOrCreateFieldNode($field);
            $this->insertElement($container, $element);
        }

        $this->databox->saveCterms($this->document);
    }

    private function ensureDocumentLoaded()
    {
        if (!$this->document) {
            $this->document = Helper::candidatesFromDatabox($this->databox);
        }
    }

    private function createElement($raw_value, $value, $context)
    {
        // <te id="C5.1384" nextid="0"/>
        //   <sy id="C5.1384.0" lng="fr" v="SL" w="sl" nextid="0"/>
        // </te>

        // Container
        $container = $this->document->createElement('te');
        $container->setAttribute('id', '');
        $container->setAttribute('nextid', 0);
        // Term
        $element = $this->document->createElement('sy');
        $element->setAttribute('id', '');
        $element->setAttribute('lng', 'fr');
        $element->setAttribute('v', $raw_value);
        $element->setAttribute('w', $value);
        if ($context) {
            $element->setAttribute('k', $context);
        }
        $element->setAttribute('nextid', 0);
        $container->appendChild($element);

        return $container;
    }

    private function findOrCreateFieldNode($field)
    {
        $this->ensureVisitorSetup();
        $element = $this->visitor->getFieldNode($field);
        if (!$element) {
            $element = $this->document->createElement('te');
            $element->setAttribute('id', '');
            $element->setAttribute('field', $field);
            $element->setAttribute('nextid', 0);
            $this->insertElement($this->document->documentElement, $element);
            $this->visitor->cacheFieldNode($field, $element);
        }

        return $element;
    }

    private function insertElement($container, $element)
    {
        $this->updateElementIdentifier($container, $element);
        $container->appendChild($element);
    }

    private function updateElementIdentifier($container, $element)
    {
        $next_id = $container->getAttribute('nextid');
        $container_id = $container->getAttribute('id');
        $id = $container_id
            ? sprintf('%s.%u', $container_id, $next_id)
            : sprintf('C%u', $next_id);
        $element->setAttribute('id', $id);
        $container->setAttribute('nextid', $next_id + 1);
        foreach ($element->childNodes as $child) {
            $this->updateElementIdentifier($element, $child);
        }
    }
}

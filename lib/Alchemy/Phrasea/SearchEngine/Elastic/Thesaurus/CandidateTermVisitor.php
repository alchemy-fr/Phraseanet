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

use Alchemy\Phrasea\SearchEngine\Elastic\Exception\CandidateTermException;
use DOMElement;

class CandidateTermVisitor implements VisitorInterface
{
    const FIELD_ATTR = 'field';
    const RAW_VALUE_ATTR = 'v';
    const TRASH_NODE_ATTR = 'delbranch';

    private $current_field;
    private $fields = array();
    private $candidates = array();

    public function visitConcept(DOMElement $element)
    {
        if ($this->isField($element)) {
            $field = $element->getAttribute(self::FIELD_ATTR);
            if ($this->current_field) {
                throw new CandidateTermException(sprintf('Trying to enter a new field while in other "%s" field.', $this->current_field));
            }
            $this->current_field = $field;
            if (isset($this->fields[$field])) {
                throw new CandidateTermException(sprintf('Duplicated field entry "%s".', $field));
            }
            $this->fields[$field] = $element;
        }
    }

    public function visitTerm(DOMElement $element)
    {
        if ($element->hasAttribute(self::RAW_VALUE_ATTR)) {
            $raw_value = $element->getAttribute(self::RAW_VALUE_ATTR);
            $this->candidates[$this->current_field][$raw_value] = $element;
        }
    }

    public function leaveConcept(DOMElement $element)
    {
        if ($this->isField($element)) {
            $this->current_field = null;
        }
    }

    private function isField(DOMElement $element)
    {
        return $element->hasAttribute(self::FIELD_ATTR)
            && !$element->hasAttribute(self::TRASH_NODE_ATTR);
    }

    public function hasTerm($field, $raw_value)
    {
        return isset($this->candidates[$field][$raw_value]);
    }

    public function getFieldNode($field)
    {
        return isset($this->fields[$field]) ? $this->fields[$field] : null;
    }

    public function cacheFieldNode($field, DOMElement $element)
    {
        if (isset($this->fields[$field])) {
            throw new LogicException(sprintf('Node for field "%s" already exists.', $field));
        }

        $this->fields[$field] = $element;
    }
}

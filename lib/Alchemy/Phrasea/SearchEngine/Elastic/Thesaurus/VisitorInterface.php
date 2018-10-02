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

use DOMElement;

interface VisitorInterface
{
    public function visitConcept(DOMElement $element);
    public function visitTerm(DOMElement $element);
    public function leaveConcept(DOMElement $element);
}

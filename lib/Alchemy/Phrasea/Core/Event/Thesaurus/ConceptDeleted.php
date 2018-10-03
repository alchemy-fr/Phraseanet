<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Thesaurus;


class ConceptDeleted extends ThesaurusEvent
{
    private $parent_id;          // the former parent
    private $deleted_synonyms;   // the former synonyms (deleted)

    public function __construct(\databox $databox, $parent_id, array $deleted_synonyms)
    {
        parent::__construct($databox);
        $this->parent_id = $parent_id;
        $this->deleted_synonyms = $deleted_synonyms;
    }

    public function getParentID()
    {
        return $this->parent_id;
    }

    public function getSynonyms()
    {
        return $this->deleted_synonyms;
    }
}

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


class ItemTrashed extends ThesaurusEvent
{
    private $parent_id;     // the former parent
    private $trash_id;      // the new id in cterms

    public function __construct(\databox $databox, $parent_id, $trash_id)
    {
        parent::__construct($databox);
        $this->parent_id = $parent_id;
        $this->trash_id = $trash_id;
    }

    public function getParentID()
    {
        return $this->parent_id;
    }

    public function getNewID()
    {
        return $this->trash_id;
    }
}

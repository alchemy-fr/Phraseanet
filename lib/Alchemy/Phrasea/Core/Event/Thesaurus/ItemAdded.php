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


class ItemAdded extends ThesaurusEvent
{
    private $new_id;      // the new id

    public function __construct(\databox $databox, $new_id)
    {
        parent::__construct($databox);
        $this->new_id = $new_id;
    }

    public function getID()
    {
        return $this->new_id;
    }

}

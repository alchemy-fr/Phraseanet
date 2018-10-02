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


class SynonymLngChanged extends ThesaurusEvent
{
    private $synonym_id;

    public function __construct(\databox $databox, $synonym_id)
    {
        parent::__construct($databox);
        $this->synonym_id = $synonym_id;
    }

    public function getID()
    {
        return $this->synonym_id;
    }
}

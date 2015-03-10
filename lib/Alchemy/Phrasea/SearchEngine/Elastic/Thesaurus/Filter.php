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

class Filter
{
    private $databox_id;

    public static function byDatabox($databox_id)
    {
        return new self($databox_id);
    }

    private function __construct($databox_id)
    {
        $this->databox_id = $databox_id;
    }

    public function getQueryFilter()
    {
        $filter = array();
        $filter['term']['databox_id'] = $this->databox_id;

        return $filter;
    }
}

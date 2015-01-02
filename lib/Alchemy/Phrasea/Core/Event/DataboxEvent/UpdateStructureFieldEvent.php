<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\DataboxEvent;

class UpdateStructureFieldEvent extends DataboxEvent
{
    private $field;
    private $data;

    public function __construct(\databox $databox, \databox_field $field, $data)
    {
        $this->field = $field;
        $this->data = $data;

        parent::__construct($databox);
    }

    /**
     * @return \databox_field
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }
}

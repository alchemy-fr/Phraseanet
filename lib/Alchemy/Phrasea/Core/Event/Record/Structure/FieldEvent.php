<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Record\Structure;

abstract class FieldEvent extends RecordStructureEvent
{
    private $field;

    public function __construct(\databox $databox, \databox_field $field)
    {
        parent::__construct($databox);

        $this->field = $field;
    }

    /**
     * @return \databox_field
     */
    public function getField()
    {
        return $this->field;
    }
}

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

abstract class StatusBitEvent extends RecordStructureEvent
{
    private $bit;

    public function __construct(\databox $databox, $bit)
    {
        parent::__construct($databox);

        $this->bit = $bit;
    }

    public function getBit()
    {
        return $this->bit;
    }
}

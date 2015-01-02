<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\RecordEvent;

use Alchemy\Phrasea\Model\Entities\Basket;
use Symfony\Component\EventDispatcher\Event as SfEvent;

class BuildSubDefEvent extends RecordEvent
{
    private $subDefName;

    public function __construct(\record_adapter $record, $subDefName)
    {
        $this->subDefName = $subDefName;
        parent::__construct($record);
    }

    public function getSubDefName()
    {
        return $this->subDefName;
    }
}

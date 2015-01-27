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

use Symfony\Component\EventDispatcher\Event;

abstract class RecordStructureEvent extends Event
{
    private $databox;

    public function __construct(\databox $databox)
    {
        $this->databox = $databox;
    }

    /**
     * @return \databox
     */
    public function getDatabox()
    {
        return $this->databox;
    }
}

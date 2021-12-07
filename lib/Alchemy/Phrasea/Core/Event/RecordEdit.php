<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event;

use Alchemy\Phrasea\Core\Event\Record\RecordEvent;

class RecordEdit extends RecordEvent
{
    /** @var array  */
    private $previousDescription;

    public function __construct(\record_adapter $record, array $previousDescription = [])
    {
        parent::__construct($record);

        $this->previousDescription = $previousDescription;
    }

    public function getPrevousDescription()
    {
        return $this->previousDescription;
    }
}

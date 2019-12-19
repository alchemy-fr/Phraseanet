<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Record;

use Alchemy\Phrasea\Model\RecordInterface;

class MetadataChangedEvent extends RecordEvent
{
    private $isNewRecord;
    private $nosubdef;

    public function __construct(RecordInterface $record, $isNewRecord = false, $nosubdef = false)
    {
        parent::__construct($record);

        $this->isNewRecord  = $isNewRecord;
        $this->nosubdef     = $nosubdef;
    }

    /**
     * @return bool
     */
    public function isNewRecord()
    {
        return $this->isNewRecord;
    }

    public function isNosubdef()
    {
        return $this->nosubdef;
    }
}

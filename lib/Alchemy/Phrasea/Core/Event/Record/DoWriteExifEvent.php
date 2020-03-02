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


class DoWriteExifEvent extends RecordEvent
{
    const ALL_SUBDEFS = 'subdefs.*';

    private $_onSubdefs;

    public function __construct(RecordInterface $record, array $onSubdefs)
    {
        parent::__construct($record);
        $this->_onSubdefs = $onSubdefs;
    }

    public function onSubdefs()
    {
        return $this->_onSubdefs;
    }
}

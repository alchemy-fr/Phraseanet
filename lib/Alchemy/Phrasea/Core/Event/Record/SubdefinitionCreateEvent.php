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

class SubdefinitionCreateEvent extends RecordEvent
{
    private $isNewRecord;

    /** @var null | array */
    private $subdefsTodo;

    public function __construct(RecordInterface $record, $isNewRecord = false, $subdefsTodo = null)
    {
        parent::__construct($record);

        $this->isNewRecord = $isNewRecord;
        $this->subdefsTodo = $subdefsTodo;
    }

    /**
     * @return bool
     */
    public function isNewRecord()
    {
        return $this->isNewRecord;
    }

    /**
     * @return array|null
     */
    public function getSubdefsTodo()
    {
        return $this->subdefsTodo;
    }
}

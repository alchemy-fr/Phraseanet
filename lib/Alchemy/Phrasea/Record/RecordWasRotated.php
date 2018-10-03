<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Record;

use Alchemy\Phrasea\Core\Event\Record\RecordEvent;
use Alchemy\Phrasea\Model\RecordInterface;

class RecordWasRotated extends RecordEvent
{
    /**
     * @var int
     */
    private $angle;

    /**
     * RecordWasRotated constructor.
     *
     * @param RecordInterface $record
     * @param int $angle
     */
    public function __construct(RecordInterface $record, $angle)
    {
        parent::__construct($record);
        $this->angle = $angle;
    }

    public function getAngle()
    {
        return $this->angle;
    }
}

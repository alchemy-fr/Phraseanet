<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Record;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\WorkerableEventInterface;
use Alchemy\Phrasea\Model\RecordInterface;
use Alchemy\Phrasea\Core\Event\WorkerableEvent;


abstract class RecordEvent extends WorkerableEvent implements WorkerableEventInterface
{
    protected $__data;
    private $record;

    public function __construct(RecordInterface $record)
    {
        $this->record = $record;
    }

    /**
     * @return RecordInterface
     */
    public function getRecord()
    {
        return $this->record;
    }



    /** **************** WorkerableEventInterface interface ************** */

    public function getAsScalars()
    {
        return [
            'sbas_id'   => $this->record->getDataboxId(),
            'record_id' => $this->record->getRecordId()
        ];
    }

    public function restoreFromScalars($data, Application $app)
    {
        $this->record = $app->getApplicationBox()
            ->get_databox($data['sbas_id'])
            ->get_record($data['record_id']);
    }

    /** ******************************************************************* */

}

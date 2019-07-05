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
use Alchemy\Phrasea\Core\Event\SerializableEventInterface;
use Alchemy\Phrasea\Model\RecordInterface;
use Symfony\Component\EventDispatcher\Event;

abstract class RecordEvent extends Event implements SerializableEventInterface, \Serializable
{
    private $record;
    protected $__data;

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



    /** **************** SerializableEventInterface interface ************** */

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

    /** ******************************************************************** */


    public function convertToMessage()
    {
        return serialize($this);
    }

    public static function restoreFromMessage($message, Application $app)
    {
        /** @var RecordEvent $event */
        $event = unserialize($message);
        $data = $event->__data;
        $event->restoreData($data, $app);
        return $event;
    }

    public function serialize()
    {
        return serialize(json_encode($this->getAsScalars()));
    }

    public function unserialize($serialized)
    {
        $this->__data = json_decode(unserialize($serialized), true);
    }
}

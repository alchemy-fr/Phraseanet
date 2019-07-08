<?php
/**
 * Created by PhpStorm.
 * User: macjy
 * Date: 2019-07-08
 * Time: 15:20
 */

namespace Alchemy\Phrasea\Core\Event;

use Alchemy\Phrasea\Application;
use Symfony\Component\EventDispatcher\Event;


class WorkerableEvent extends Event implements WorkerableEventInterface, \Serializable
{
    protected $__data;

    public function convertToWorkerMessage()
    {
        return serialize($this);
    }

    public static function restoreFromWorkerMessage($message, Application $app)
    {
        /** @var WorkerableEvent $event */
        $event = unserialize($message);
        $data = $event->__data;
        $event->restoreFromScalars($data, $app);
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

    /** **************** WorkerableEventInterface interface ************** */

    public function getAsScalars()
    {
        return null;
    }

    public function restoreFromScalars($data, Application $app)
    {
    }

    /** ****************************************************************** */

}
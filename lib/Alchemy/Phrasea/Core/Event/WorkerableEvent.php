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


/**
 * Class WorkerableEvent
 * @package Alchemy\Phrasea\Core\Event
 *
 * howto intercept an event to push it into a queue, then pop it later (worker) and really run it :
 *
 * - implement "WorkerableEventInterface"
 *
 * - create a suscriber to catch the event, with priority > 0 (plugin ?)
 *   - /!\ if the event is marked "isReplayed()", it came back from a worker via api/v2/worker/execute, so
 *          - ... do nothing, the phraseanet internal suscriber (priority=0) will do the real job
 *   - else the event is a first shot
 *          - call "convertToWorkerMessage(...)"
 *          - push the message to a queue
 *          - stop propagation to prevent the phraseanet internal suscriber to do the job
 *
 * - the worker
 *   - pop the message from queue
 *   - post the message to api/v2/worker/execute
 *
 * - the api/v2/worker/execute will :
 *   - restore it as an event with "restoreFromWorkerMessage(...)"
 *   - /!\ mark the event as replay with "setReplayed()",
 *     to tell the suscriber not to push it on queue again
 *   - dispatch the restored event
 *
 */
class WorkerableEvent extends Event implements WorkerableEventInterface, \Serializable
{
    protected $__data;
    private $__replayed = false;    // prevent endless suscriber catch

    /**
     * the suscriber should mark the "poped from queue" event as replayed
     */
    public function setReplayed()
    {
        $this->__replayed = true;
    }

    /**
     * the suscriber should push to Q only if isReplayed()===false
     * @return bool
     */
    public function isReplayed()
    {
        return $this->__replayed;
    }


    public function convertToWorkerMessage($eventName)
    {
        return json_encode([
            'name' => $eventName,
            'data' => serialize($this)
        ]);
    }

    public static function restoreFromWorkerMessage($message, Application $app)
    {
        $o = json_decode($message, true);

        /** @var WorkerableEvent $event */
        $event = unserialize($o['data']);
        $data = $event->__data;
        $event->restoreFromScalars($data, $app);

        return [
            'name' => $o['name'],
            'event' => $event
        ];
    }

    public function serialize()
    {
        // return serialize(json_encode($this->getAsScalars()));
        return serialize($this->getAsScalars());
    }

    public function unserialize($serialized)
    {
        // $this->__data = json_decode(unserialize($serialized), true);
        $this->__data = unserialize($serialized);
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
<?php
/**
 * Created by PhpStorm.
 * User: macjy
 * Date: 2019-07-08
 * Time: 15:20
 */

namespace Alchemy\Phrasea\Core\Event;

use Alchemy\Phrasea\Application;
use Firebase\JWT\JWT;
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
     * todo : define the jwt parameters in settings
     */
    const JWT_KEY = 'this-is-the-key';
    const JWT_TTL = (24*60*60);    // 24h as seconds

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

    /**
     * convert the event to a message (jwt or json)
     * too bad, we cannot get the eventname from the event, it must be passed as arg.
     *
     * @param $eventName
     * @return false|string
     */
    public function convertToWorkerMessage($eventName)
    {
        $payload = [
            'iat'  => time(),
            'iss'  => 0,        // should be a user id    $issuer->getId(),
            'exp'  => time() + self::JWT_TTL,
            'data' => [
                'name' => $eventName,
                'data' => serialize($this)
            ]
        ];

        if(self::JWT_KEY) {
            return JWT::encode(
                $payload,
                self::JWT_KEY,
                'HS256'
            );
        }
        else {
            // to debug : message is only json
            return json_encode($payload);
        }
    }

    /**
     * backconvert a message to a pair event-name / event
     *
     * @param $message
     * @param Application $app
     * @return array
     */
    public static function restoreFromWorkerMessage($message, Application $app)
    {
        if(self::JWT_KEY) {
            $payload = (array)JWT::decode($message, self::JWT_KEY, ['HS256']);
        }
        else {
            $payload = json_decode($message, true);
        }
        $o = (array)$payload['data'];

        /** @var WorkerableEvent $event */
        $event = unserialize($o['data']);   // will call the magic unserialize() which will set __data
        $data = $event->__data;
        $event->restoreFromScalars($data, $app);

        return [
            'name'  => $o['name'],
            'event' => $event
        ];
    }

    /**
     * magic
     *
     * @return string
     */
    public function serialize()
    {
        return serialize($this->getAsScalars());
    }

    /**
     * magic
     * too bad this will build an object (event), no way to "return" data, so we use the propery "__data"
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
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
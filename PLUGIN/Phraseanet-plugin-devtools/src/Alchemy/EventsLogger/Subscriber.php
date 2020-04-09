<?php

/*
 * This file is part of Phraseanet Mail-Log plugin
 *
 * (c) 2005-2020 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\DevToolsPlugin\EventsLogger;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Core\Event\Acl\AclEvent;
use Alchemy\Phrasea\Core\Event\Collection\CollectionEvent;
use Alchemy\Phrasea\Core\Event\Databox\DataboxEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvent;
use Alchemy\Phrasea\Core\Event\Record\Structure\RecordStructureEvent;
use Alchemy\Phrasea\Core\Event\User\UserEvent;
use Symfony\Component\EventDispatcher\Event as SfEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class Subscriber implements EventSubscriberInterface
{
    private $eventsCount = 0;

    private $clientSocket = null;


    public function __construct(PropertyAccess $config)
    {
        $socketname = 'unix:///tmp/phrEvts_' . $config->get(['main', 'key']);

        // is the server (bin/console eventlogger:listenevents) does not run, no pb to fail here with no socket
        $this->clientSocket = @stream_socket_client($socketname);
    }

    public function __destruct()
    {
        if($this->clientSocket) {
            socket_close($this->clientSocket);
        }
    }

    private function log($namespace, $eventName, $msg = "")
    {
        if(!$this->clientSocket) {
            return;
        }

        // insert a blank line between each process
        if($this->eventsCount++ === 0) {
            @fwrite($this->clientSocket, PHP_EOL);
        }

        if($msg !== '') {
            $msg = ' [' . $msg . ']';
        }
        $msg = sprintf('%s.%s%s', $namespace, $eventName, $msg);
        @fwrite($this->clientSocket, $msg . PHP_EOL);
    }

    public function onPhraseaEvents(/** @noinspection PhpUnusedParameterInspection */ SfEvent $event, $eventName)
    {
        $this->log('PhraseaEvents', $eventName);
    }

    public function onAclEvents(/** @noinspection PhpUnusedParameterInspection */ AclEvent $event, $eventName)
    {
        $this->log('AclEvents', $eventName);
    }

    public function onCollectionEvents(CollectionEvent $event, $eventName)
    {
        $this->log('CollectionEvents', $eventName, sprintf('base_id=%s', $event->getCollection()->get_base_id()));
    }

    public function onDataboxEvents(DataboxEvent $event, $eventName)
    {
        $this->log('DataboxEvents', $eventName, sprintf('sbas_id=%s', $event->getDatabox()->get_sbas_id()));
    }

    public function onRecordEvents(RecordEvent $event, $eventName)
    {
        $this->log('RecordEvents', $eventName, sprintf("record=%s", $event->getRecord()->getId()));
    }

    public function onRecordStructureEvents(RecordStructureEvent $event, $eventName)
    {
        $this->log('RecordStructureEvents', $eventName, sprintf('sbas_id=%s', $event->getDatabox()->get_sbas_id()));
    }

    public function onUserEvents(UserEvent $event, $eventName)
    {
        $this->log('UserEvents', $eventName, sprintf('user_id=%s', $event->getUser()->getId()));
    }

    public function onEvents(/** @noinspection PhpUnusedParameterInspection */ SfEvent $event, $eventName)
    {
        $this->log('_Events', $eventName);
    }


    // ==========================================

    /**
     * @uses onPhraseaEvents
     * @uses onAclEvents
     * @uses onCollectionEvents
     * @uses onDataboxEvents
     * @uses onRecordEvents
     * @uses onRecordStructureEvents
     * @uses onUserEvents
     * @uses onEvents
     */
    public static function getSubscribedEvents()
    {
        $t = [];
        foreach(EventsSources::getSources() as $k => $v) {
            $handler = 'on' . $k;
            if(!method_exists(__CLASS__, $handler)) {
                $handler = 'onEvents';
            }
            foreach($v['constants'] as $c) {
                $t[$c] = $handler;
            }
        }

        return $t;
    }

}
